<?php

namespace App\Filament\Resources\Staff\Tables;

use App\Filament\Resources\TeachingAssignments\TeachingAssignmentResource;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->disk('public')
                    ->visibility('public')
                    ->defaultImageUrl(asset('images/branding/school-dice-logo-icon.png'))
                    ->circular(),
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('full_name')
                    ->label('Staff')
                    ->searchable(query: function ($query, string $search) {
                        return $query
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('staff_number', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->sortable(['last_name'])
                    ->weight('semibold')
                    ->description(fn (Staff $record): string => collect([$record->staff_number, $record->email])->filter()->implode('  ·  ')),
                TextColumn::make('staff_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === Staff::TYPE_TEACHING ? 'Teaching' : 'Non-teaching')
                    ->color(fn (string $state): string => $state === Staff::TYPE_TEACHING ? 'success' : 'gray'),
                TextColumn::make('department.name')
                    ->label('Unit')
                    ->placeholder('Not set')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Staff $record): ?string => $record->job_title),
                TextColumn::make('highest_qualification')
                    ->label('Qualification')
                    ->formatStateUsing(fn (?string $state): ?string => $state ? (Staff::QUALIFICATION_OPTIONS[$state] ?? $state) : null)
                    ->placeholder('Not set')
                    ->toggleable(),
                TextColumn::make('employment_type')->badge(),
                TextColumn::make('user.email')
                    ->label('Portal Login')
                    ->placeholder('No login')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'on_leave' => 'info',
                        'suspended' => 'warning',
                        'resigned', 'terminated' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('staff_type')
                    ->label('Staff type')
                    ->options([
                        Staff::TYPE_TEACHING => 'Teaching staff',
                        Staff::TYPE_NON_TEACHING => 'Non-teaching staff',
                    ]),
                SelectFilter::make('department_id')
                    ->label('Department / Unit')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'on_leave' => 'On leave',
                        'suspended' => 'Suspended',
                        'resigned' => 'Resigned',
                        'terminated' => 'Terminated',
                    ]),
            ])
            ->recordActions([
                Action::make('assignTeaching')
                    ->label('Assign Form Class')
                    ->icon('heroicon-o-academic-cap')
                    ->color('primary')
                    ->visible(fn (Staff $record): bool => $record->staff_type === Staff::TYPE_TEACHING && ! self::hasFormTeacherAssignment($record))
                    ->url(fn (Staff $record): string => TeachingAssignmentResource::getUrl('create', [
                        'tenant' => Filament::getTenant(),
                        'staff_id' => $record->getKey(),
                    ])),
                Action::make('assignSubjects')
                    ->label('Assign Subjects')
                    ->icon('heroicon-o-book-open')
                    ->color('info')
                    ->modalWidth('3xl')
                    ->visible(fn (Staff $record): bool => $record->staff_type === Staff::TYPE_TEACHING)
                    ->schema([
                        Select::make('academic_year_id')
                            ->label('Session')
                            ->options(fn (): array => AcademicYear::query()
                                ->where('school_id', Filament::getTenant()?->getKey())
                                ->orderByDesc('starts_on')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('term_id')
                            ->label('Term')
                            ->options(fn (): array => Term::query()
                                ->where('school_id', Filament::getTenant()?->getKey())
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload(),
                        Select::make('subject_id')
                            ->label('Subject')
                            ->options(fn (): array => Subject::query()
                                ->where('school_id', Filament::getTenant()?->getKey())
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('school_class_ids')
                            ->label('Classes')
                            ->options(fn (): array => SchoolClass::query()
                                ->where('school_id', Filament::getTenant()?->getKey())
                                ->orderBy('level')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        Select::make('class_section_ids')
                            ->label('Arms')
                            ->helperText('Leave empty if the teacher handles the whole selected class.')
                            ->options(fn (Get $get): array => ClassSection::query()
                                ->where('school_id', Filament::getTenant()?->getKey())
                                ->when($get('school_class_ids'), fn ($query, array $classIds) => $query->whereIn('school_class_id', $classIds))
                                ->with('schoolClass')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (ClassSection $arm): array => [$arm->getKey() => "{$arm->schoolClass?->name} {$arm->name}"])
                                ->all())
                            ->multiple()
                            ->searchable()
                            ->preload(),
                        TextInput::make('weekly_periods')
                            ->label('Weekly periods')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->default(3)
                            ->required(),
                        Toggle::make('is_compulsory')
                            ->label('Compulsory subject')
                            ->default(true),
                    ])
                    ->action(function (Staff $record, array $data): void {
                        $tenant = Filament::getTenant();

                        if (! $tenant) {
                            return;
                        }

                        $classIds = collect($data['school_class_ids'] ?? [])->filter()->values();
                        $arms = ClassSection::query()
                            ->whereIn('id', $data['class_section_ids'] ?? [])
                            ->get()
                            ->groupBy('school_class_id');
                        $created = 0;

                        DB::transaction(function () use ($record, $data, $tenant, $classIds, $arms, &$created): void {
                            foreach ($classIds as $classId) {
                                ClassSubject::query()->updateOrCreate(
                                    [
                                        'school_class_id' => $classId,
                                        'subject_id' => $data['subject_id'],
                                    ],
                                    [
                                        'school_id' => $tenant->getKey(),
                                        'staff_id' => $record->getKey(),
                                        'teacher_id' => $record->user_id,
                                        'weekly_periods' => $data['weekly_periods'],
                                        'is_compulsory' => $data['is_compulsory'] ?? true,
                                        'is_active' => true,
                                    ],
                                );

                                $selectedArms = $arms->get($classId, collect());

                                if ($selectedArms->isEmpty()) {
                                    self::saveSubjectTeachingAssignment($record, $data, (int) $classId, null);
                                    $created++;

                                    continue;
                                }

                                foreach ($selectedArms as $arm) {
                                    self::saveSubjectTeachingAssignment($record, $data, (int) $classId, $arm->getKey());
                                    $created++;
                                }
                            }
                        });

                        Notification::make()
                            ->title('Subject teaching load saved')
                            ->body("{$record->full_name} now has {$created} subject assignment(s).")
                            ->success()
                            ->send();
                    }),
                Action::make('deassignSubjects')
                    ->label('Deassign Subjects')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->modalWidth('2xl')
                    ->visible(fn (Staff $record): bool => $record->staff_type === Staff::TYPE_TEACHING && self::hasSubjectAssignments($record))
                    ->schema([
                        Select::make('assignment_ids')
                            ->label('Subject assignments')
                            ->options(fn (Staff $record): array => self::subjectAssignmentOptions($record))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalDescription('Selected subject assignments will be removed from this teacher. The subject can remain on the class, but it will no longer be tied to this teacher.')
                    ->action(function (Staff $record, array $data): void {
                        $assignmentIds = collect($data['assignment_ids'] ?? [])->filter()->values();

                        if ($assignmentIds->isEmpty()) {
                            return;
                        }

                        DB::transaction(function () use ($record, $assignmentIds): void {
                            $assignments = TeachingAssignment::query()
                                ->where('school_id', Filament::getTenant()?->getKey() ?? $record->school_id)
                                ->where('staff_id', $record->getKey())
                                ->where('assignment_role', TeachingAssignment::ROLE_SUBJECT_TEACHER)
                                ->whereIn('id', $assignmentIds)
                                ->get();

                            $affectedClassSubjects = $assignments
                                ->map(fn (TeachingAssignment $assignment): array => [
                                    'school_class_id' => $assignment->school_class_id,
                                    'subject_id' => $assignment->subject_id,
                                ])
                                ->unique(fn (array $item): string => $item['school_class_id'].'-'.$item['subject_id'])
                                ->values();

                            $assignments->each->delete();

                            $affectedClassSubjects->each(function (array $item) use ($record): void {
                                self::refreshClassSubjectTeacher($record, (int) $item['school_class_id'], (int) $item['subject_id']);
                            });
                        });

                        Notification::make()
                            ->title('Subject assignment removed')
                            ->body("{$record->full_name} has been removed from the selected subject assignment(s).")
                            ->success()
                            ->send();
                    }),
                Action::make('createLogin')
                    ->label('Create Login')
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->visible(fn (Staff $record): bool => blank($record->user_id))
                    ->schema([
                        TextInput::make('email')
                            ->label('Login email')
                            ->email()
                            ->required()
                            ->default(fn (Staff $record): ?string => $record->email)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label('Temporary password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8),
                    ])
                    ->action(function (Staff $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $user = User::query()->updateOrCreate(
                                ['email' => $data['email']],
                                [
                                    'name' => $record->full_name,
                                    'password' => Hash::make($data['password']),
                                    'is_platform_admin' => false,
                                    'is_active' => true,
                                ],
                            );

                            $user->schools()->syncWithoutDetaching([
                                $record->school_id => [
                                    'role' => $record->staff_type === Staff::TYPE_TEACHING ? 'teacher' : 'staff',
                                    'is_primary' => false,
                                ],
                            ]);

                            $record->forceFill([
                                'user_id' => $user->getKey(),
                                'email' => $record->email ?: $data['email'],
                            ])->save();
                        });

                        Notification::make()
                            ->title('Login account created')
                            ->body("{$record->full_name} can now sign in with {$data['email']}.")
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function hasFormTeacherAssignment(Staff $staff): bool
    {
        return TeachingAssignment::query()
            ->where('school_id', Filament::getTenant()?->getKey() ?? $staff->school_id)
            ->where('staff_id', $staff->getKey())
            ->whereIn('assignment_role', [
                TeachingAssignment::ROLE_FORM_TEACHER,
                TeachingAssignment::ROLE_ASSISTANT_FORM_TEACHER,
            ])
            ->where('is_active', true)
            ->exists();
    }

    protected static function hasSubjectAssignments(Staff $staff): bool
    {
        return TeachingAssignment::query()
            ->where('school_id', Filament::getTenant()?->getKey() ?? $staff->school_id)
            ->where('staff_id', $staff->getKey())
            ->where('assignment_role', TeachingAssignment::ROLE_SUBJECT_TEACHER)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * @return array<int, string>
     */
    protected static function subjectAssignmentOptions(Staff $staff): array
    {
        return TeachingAssignment::query()
            ->with(['academicYear', 'term', 'schoolClass', 'classSection', 'subject'])
            ->where('school_id', Filament::getTenant()?->getKey() ?? $staff->school_id)
            ->where('staff_id', $staff->getKey())
            ->where('assignment_role', TeachingAssignment::ROLE_SUBJECT_TEACHER)
            ->where('is_active', true)
            ->latest()
            ->get()
            ->mapWithKeys(function (TeachingAssignment $assignment): array {
                $class = collect([
                    $assignment->schoolClass?->name,
                    $assignment->classSection?->name,
                ])->filter()->implode(' ');
                $period = collect([
                    $assignment->academicYear?->name,
                    $assignment->term?->name,
                ])->filter()->implode(' / ');

                return [
                    $assignment->getKey() => "{$assignment->subject?->name} - {$class}".($period ? " ({$period})" : ''),
                ];
            })
            ->all();
    }

    protected static function saveSubjectTeachingAssignment(Staff $staff, array $data, int $classId, ?int $armId): void
    {
        TeachingAssignment::query()->updateOrCreate(
            [
                'staff_id' => $staff->getKey(),
                'academic_year_id' => $data['academic_year_id'],
                'term_id' => $data['term_id'] ?? null,
                'school_class_id' => $classId,
                'class_section_id' => $armId,
                'subject_id' => $data['subject_id'],
                'assignment_role' => TeachingAssignment::ROLE_SUBJECT_TEACHER,
            ],
            [
                'school_id' => Filament::getTenant()?->getKey() ?? $staff->school_id,
                'is_class_teacher' => false,
                'is_active' => true,
            ],
        );
    }

    protected static function refreshClassSubjectTeacher(Staff $removedStaff, int $classId, int $subjectId): void
    {
        $classSubject = ClassSubject::query()
            ->where('school_class_id', $classId)
            ->where('subject_id', $subjectId)
            ->first();

        if (! $classSubject || (int) $classSubject->staff_id !== (int) $removedStaff->getKey()) {
            return;
        }

        $replacement = TeachingAssignment::query()
            ->with('staff')
            ->where('school_id', Filament::getTenant()?->getKey() ?? $removedStaff->school_id)
            ->where('school_class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('assignment_role', TeachingAssignment::ROLE_SUBJECT_TEACHER)
            ->where('is_active', true)
            ->latest()
            ->first();

        $classSubject->forceFill([
            'staff_id' => $replacement?->staff_id,
            'teacher_id' => $replacement?->staff?->user_id,
        ])->save();
    }
}
