<?php

namespace App\Filament\Resources\ReportCards\Tables;

use App\Models\AssessmentComponent;
use App\Models\ClassSection;
use App\Models\ReportCard;
use App\Models\ReportCardTraitRating;
use App\Models\ResultTraitItem;
use App\Models\SchoolClass;
use App\Models\StudentScore;
use App\Support\TeacherWorkspace;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportCardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                if (! TeacherWorkspace::isTeacher()) {
                    if (Filament::getCurrentPanel()?->getId() === 'school') {
                        $query->whereIn('status', ['form_teacher_reviewed', 'approved', 'published'])
                            ->orderByRaw('principal_comment is not null');
                    }

                    return $query;
                }

                $tenant = Filament::getTenant();
                $formAssignments = TeacherWorkspace::formAssignments();
                $formClassIds = $formAssignments->pluck('school_class_id')->filter()->unique()->values()->all();
                $formArmIds = $formAssignments->pluck('class_section_id')->filter()->unique()->values()->all();

                if ($formClassIds === []) {
                    return $query->whereRaw('1 = 0');
                }

                return $query
                    ->whereHas('student.enrollments', function (Builder $query) use ($tenant, $formClassIds, $formArmIds): void {
                        $query
                            ->where('school_id', $tenant?->getKey())
                            ->whereIn('school_class_id', $formClassIds)
                            ->where('status', 'active')
                            ->when($formArmIds !== [], fn (Builder $query) => $query->whereIn('class_section_id', $formArmIds));
                    })
                    ->orderByRaw('teacher_comment is not null');
            })
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                ImageColumn::make('student.photo_path')
                    ->label('Photo')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(url('/images/branding/school-dice-logo-ful.png')),
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable(query: fn ($query, string $search) => $query->whereHas(
                        'student',
                        fn ($query) => $query
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('admission_number', 'like', "%{$search}%"),
                    ))
                    ->weight('semibold')
                    ->description(fn ($record): ?string => $record->student?->admission_number),
                TextColumn::make('academicYear.name')
                    ->label('Session')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('term.name')
                    ->label('Term')
                    ->placeholder('Whole session')
                    ->sortable(),
                TextColumn::make('exam.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.enrollments.schoolClass.name')
                    ->label('Class')
                    ->state(fn ($record): string => self::placementLabel($record))
                    ->toggleable(),
                ...self::componentColumns(),
                TextColumn::make('total_score')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('average_score')
                    ->label('Average')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('position')
                    ->label('Class pos.')
                    ->sortable(),
                TextColumn::make('attendance')
                    ->label('Attendance')
                    ->state(fn (ReportCard $record): string => "{$record->attendance_present_days}/{$record->attendance_total_days}")
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'published' => 'success',
                    'approved' => 'info',
                    'form_teacher_reviewed' => 'warning',
                    default => 'gray',
                })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'form_teacher_reviewed' => 'Form teacher reviewed',
                        default => str($state)->replace('_', ' ')->title(),
                    }),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                SelectFilter::make('academic_year_id')
                    ->label('Session')
                    ->relationship('academicYear', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('term_id')
                    ->label('Term')
                    ->relationship('term', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('exam_id')->label('Exam')->relationship('exam', 'name')->searchable()->preload(),
                SelectFilter::make('school_class_id')
                    ->label('Class')
                    ->options(fn (): array => SchoolClass::query()
                        ->when(Filament::getTenant(), fn (Builder $query, $tenant) => $query->where('school_id', $tenant->getKey()))
                        ->when(TeacherWorkspace::isTeacher(), fn (Builder $query) => $query->whereIn('id', TeacherWorkspace::formClassIds()))
                        ->orderBy('level')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('student.enrollments', fn (Builder $query) => $query->where('school_class_id', $data['value']))
                        : $query),
                SelectFilter::make('class_section_id')
                    ->label('Arm')
                    ->options(fn (): array => ClassSection::query()
                        ->when(Filament::getTenant(), fn (Builder $query, $tenant) => $query->where('school_id', $tenant->getKey()))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('student.enrollments', fn (Builder $query) => $query->where('class_section_id', $data['value']))
                        : $query),
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'form_teacher_reviewed' => 'Form teacher reviewed',
                    'approved' => 'Approved',
                    'published' => 'Published',
                ]),
            ])
            ->recordActions([
                Action::make('reviewResult')
                    ->label(fn (): string => TeacherWorkspace::isTeacher() ? 'Review Result' : 'Principal Review')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->visible(fn (ReportCard $record): bool => TeacherWorkspace::isTeacher() && $record->status !== 'published')
                    ->modalWidth('4xl')
                    ->fillForm(fn (ReportCard $record): array => [
                        'attendance_total_days' => $record->attendance_total_days,
                        'attendance_present_days' => $record->attendance_present_days,
                        'attendance_absent_days' => $record->attendance_absent_days,
                        'affective_ratings' => self::ratingRows($record, 'affective'),
                        'psychomotor_ratings' => self::ratingRows($record, 'psychomotor'),
                        'teacher_comment' => $record->teacher_comment,
                    ])
                    ->schema([
                        Section::make('Attendance summary')
                            ->schema([
                                TextInput::make('attendance_total_days')
                                    ->label('School days')
                                    ->numeric()
                                    ->minValue(0)
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set): mixed => $set('attendance_absent_days', max(0, (int) $get('attendance_total_days') - (int) $get('attendance_present_days'))))
                                    ->required(),
                                TextInput::make('attendance_present_days')
                                    ->label('Days present')
                                    ->numeric()
                                    ->minValue(0)
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set): mixed => $set('attendance_absent_days', max(0, (int) $get('attendance_total_days') - (int) $get('attendance_present_days'))))
                                    ->required(),
                                TextInput::make('attendance_absent_days')
                                    ->label('Days absent')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                        Section::make('Affective domain')
                            ->schema([
                                Repeater::make('affective_ratings')
                                    ->hiddenLabel()
                                    ->schema(self::ratingSchema())
                                    ->columns(2)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false),
                            ])
                            ->columnSpanFull(),
                        Section::make('Psychomotor domain')
                            ->schema([
                                Repeater::make('psychomotor_ratings')
                                    ->hiddenLabel()
                                    ->schema(self::ratingSchema())
                                    ->columns(2)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false),
                            ])
                            ->columnSpanFull(),
                        Textarea::make('teacher_comment')
                            ->label('Class teacher remark')
                            ->rows(4)
                            ->columnSpanFull()
                            ->required(),
                    ])
                    ->action(function (ReportCard $record, array $data): void {
                        $record->forceFill([
                            'attendance_total_days' => $data['attendance_total_days'] ?? 0,
                            'attendance_present_days' => $data['attendance_present_days'] ?? 0,
                            'attendance_absent_days' => max(0, (int) ($data['attendance_total_days'] ?? 0) - (int) ($data['attendance_present_days'] ?? 0)),
                            'teacher_comment' => $data['teacher_comment'],
                            'status' => $record->status === 'draft' ? 'form_teacher_reviewed' : $record->status,
                        ])->save();

                        self::saveRatings($record, [
                            ...($data['affective_ratings'] ?? []),
                            ...($data['psychomotor_ratings'] ?? []),
                        ]);

                        Notification::make()
                            ->title('Result review saved')
                            ->success()
                            ->send();
                    }),
                Action::make('principalRemark')
                    ->label('Head Remark')
                    ->icon('heroicon-o-pencil-square')
                    ->color('info')
                    ->visible(fn (): bool => ! TeacherWorkspace::isTeacher())
                    ->fillForm(fn (ReportCard $record): array => [
                        'principal_comment' => $record->principal_comment,
                    ])
                    ->schema([
                        Textarea::make('principal_comment')
                            ->label('Head teacher / principal remark')
                            ->rows(4)
                            ->required(),
                    ])
                    ->action(function (ReportCard $record, array $data): void {
                        $record->forceFill([
                            'principal_comment' => $data['principal_comment'],
                            'status' => in_array($record->status, ['draft', 'form_teacher_reviewed'], true) ? 'approved' : $record->status,
                        ])->save();

                        Notification::make()
                            ->title('Head remark saved')
                            ->success()
                            ->send();
                    }),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (ReportCard $record): bool => ! TeacherWorkspace::isTeacher() && ! in_array($record->status, ['approved', 'published'], true))
                    ->requiresConfirmation()
                    ->action(function (ReportCard $record): void {
                        $record->forceFill(['status' => 'approved'])->save();

                        Notification::make()
                            ->title('Report card approved')
                            ->success()
                            ->send();
                    }),
                Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (ReportCard $record): bool => ! TeacherWorkspace::isTeacher() && $record->status !== 'published')
                    ->requiresConfirmation()
                    ->action(function (ReportCard $record): void {
                        $record->forceFill([
                            'status' => 'published',
                            'published_at' => now(),
                        ])->save();

                        Notification::make()
                            ->title('Report card published')
                            ->success()
                            ->send();
                    }),
                Action::make('downloadPdf')
                    ->label('Download Result')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record): string => route('report-cards.pdf', $record))
                    ->openUrlInNewTab(),
                EditAction::make()
                    ->visible(fn (): bool => ! TeacherWorkspace::isTeacher()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approveSelected')
                        ->label('Approve selected')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(fn (ReportCard $record) => $record->forceFill(['status' => 'approved'])->save());

                            Notification::make()
                                ->title('Selected report cards approved')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('publishSelected')
                        ->label('Publish selected')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(fn (ReportCard $record) => $record->forceFill([
                                'status' => 'published',
                                'published_at' => now(),
                            ])->save());

                            Notification::make()
                                ->title('Selected report cards published')
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ])->visible(fn (): bool => ! TeacherWorkspace::isTeacher()),
            ])
            ->defaultSort('average_score', 'desc')
            ->emptyStateHeading('No student results found')
            ->emptyStateDescription(TeacherWorkspace::isTeacher()
                ? 'Submitted scores will appear here for your class. Add attendance, ratings, and class teacher remarks.'
                : 'Results appear here after the class teacher has reviewed them.')
            ->striped();
    }

    /**
     * @return array<int, TextColumn>
     */
    protected static function componentColumns(): array
    {
        return AssessmentComponent::query()
            ->when(Filament::getTenant(), fn (Builder $query, $tenant) => $query->where('school_id', $tenant->getKey()))
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->unique(fn (AssessmentComponent $component): string => str($component->name)->lower()->toString())
            ->take(6)
            ->values()
            ->map(fn (AssessmentComponent $component): TextColumn => TextColumn::make('component_'.(string) str($component->name)->slug('_'))
                ->label($component->name)
                ->alignCenter()
                ->state(fn (ReportCard $record): string => self::componentScore($record, $component->name))
                ->badge()
                ->color('gray'))
            ->all();
    }

    protected static function componentScore(ReportCard $record, string $componentName): string
    {
        $score = StudentScore::query()
            ->where('exam_id', $record->exam_id)
            ->where('student_id', $record->student_id)
            ->whereHas('assessmentComponent', fn (Builder $query) => $query->where('name', $componentName))
            ->whereIn('status', ['submitted', 'approved'])
            ->sum('score');

        return number_format((float) $score, 2);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    /**
     * @return array<int, mixed>
     */
    protected static function ratingSchema(): array
    {
        return [
            Hidden::make('result_trait_item_id'),
            TextInput::make('trait')
                ->disabled()
                ->dehydrated(false),
            Select::make('rating')
                ->options(fn (Get $get): array => self::ratingOptions((int) ($get('max_rating') ?: 5)))
                ->required(),
            Hidden::make('max_rating'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function ratingRows(ReportCard $record, string $category): array
    {
        $existing = $record->traitRatings()->get()->keyBy('result_trait_item_id');

        return ResultTraitItem::query()
            ->where('school_id', $record->school_id)
            ->where('category', $category)
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->map(fn (ResultTraitItem $item): array => [
                'result_trait_item_id' => $item->getKey(),
                'trait' => $item->name,
                'rating' => $existing->get($item->getKey())?->rating,
                'max_rating' => $item->max_rating,
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected static function ratingOptions(int $maxRating): array
    {
        return collect(range(1, max(1, $maxRating)))
            ->mapWithKeys(fn (int $rating): array => [$rating => str_repeat('*', $rating).' '.$rating])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $ratings
     */
    protected static function saveRatings(ReportCard $record, array $ratings): void
    {
        foreach ($ratings as $rating) {
            if (blank($rating['result_trait_item_id'] ?? null)) {
                continue;
            }

            ReportCardTraitRating::query()->updateOrCreate(
                [
                    'report_card_id' => $record->getKey(),
                    'result_trait_item_id' => $rating['result_trait_item_id'],
                ],
                [
                    'school_id' => $record->school_id,
                    'rating' => $rating['rating'] ?? null,
                ],
            );
        }
    }

    protected static function placementLabel($record): string
    {
        $placement = $record->student
            ?->enrollments
            ->where('academic_year_id', $record->academic_year_id)
            ->when($record->term_id, fn ($enrollments) => $enrollments->where('term_id', $record->term_id))
            ->sortByDesc('enrolled_on')
            ->first()
            ?? $record->student?->enrollments->sortByDesc('enrolled_on')->first();

        return collect([
            $placement?->schoolClass?->name,
            $placement?->classSection?->name,
        ])->filter()->implode(' ') ?: 'Not set';
    }
}
