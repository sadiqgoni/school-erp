<?php

namespace App\Filament\Resources\StudentScores\Pages;

use App\Filament\Resources\CompiledResults\Pages\ListCompiledResults;
use App\Filament\Resources\StudentScores\StudentScoreResource;
use App\Filament\Support\ClassTabs;
use App\Models\AssessmentComponent;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\StudentScore;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Support\TeacherWorkspace;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\DB;

class ListStudentScores extends ListRecords
{
    protected static string $resource = StudentScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('enterScores')
                ->label('Enter Scores')
                ->icon('heroicon-o-pencil-square')
                ->modalHeading('Enter student scores')
                ->modalDescription('Choose the exam, component, subject, and class. The student list will load automatically from active class placements.')
                ->modalSubmitActionLabel('Save scores')
                ->modalWidth('7xl')
                ->schema([
                    Section::make('Assessment')
                        ->schema([
                            Select::make('exam_id')
                                ->label('Exam')
                                ->options(fn (): array => Exam::query()
                                    ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                    ->orderByDesc('created_at')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn ($state, Get $get, Set $set) => self::refreshScoreRows($get, $set)),
                            Select::make('assessment_component_id')
                                ->label('Component')
                                ->options(fn (Get $get): array => AssessmentComponent::query()
                                    ->when($get('exam_id'), fn ($query, $examId) => $query->where('exam_id', $examId))
                                    ->where('is_active', true)
                                    ->orderBy('position')
                                    ->get()
                                    ->mapWithKeys(fn (AssessmentComponent $component): array => [
                                        $component->getKey() => "{$component->name} ({$component->max_score} marks)",
                                    ])
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn ($state, Get $get, Set $set) => self::refreshScoreRows($get, $set)),
                            Select::make('status')
                                ->label('Save mode')
                                ->required()
                                ->default('draft')
                                ->options([
                                    'draft' => 'Save as draft',
                                    'submitted' => 'Submit scores',
                                ]),
                        ])
                        ->columns(3),
                    Section::make(fn (): string => self::isTeacherUser() ? 'Subject / class' : 'Class and subject')
                        ->schema([
                            Select::make('class_subject_id')
                                ->label('Subject / class')
                                ->options(fn (): array => self::teacherClassSubjectOptions())
                                ->searchable()
                                ->preload()
                                ->required(fn (): bool => self::isTeacherUser())
                                ->visible(fn (): bool => self::isTeacherUser())
                                ->live()
                                ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                    [$classSubjectId, $armId] = self::parseClassSubjectValue($state);
                                    $classSubject = $classSubjectId ? ClassSubject::query()->find($classSubjectId) : null;

                                    $set('subject_id', $classSubject?->subject_id);
                                    $set('school_class_id', $classSubject?->school_class_id);
                                    $set('class_section_id', $armId ?: self::defaultTeacherArmId($classSubject));

                                    self::refreshScoreRows($get, $set);
                                }),
                            Select::make('subject_id')
                                ->label('Subject')
                                ->options(fn (Get $get): array => Subject::query()
                                    ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                    ->when(self::teacherSubjectIds($get('exam_id')), fn ($query, array $subjectIds) => $query->whereIn('id', $subjectIds))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->visible(fn (): bool => ! self::isTeacherUser())
                                ->live()
                                ->afterStateUpdated(fn ($state, Get $get, Set $set) => self::refreshScoreRows($get, $set)),
                            Select::make('school_class_id')
                                ->label('Class')
                                ->options(fn (Get $get): array => SchoolClass::query()
                                    ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                    ->when(self::teacherClassIds($get('exam_id'), $get('subject_id')), fn ($query, array $classIds) => $query->whereIn('id', $classIds))
                                    ->orderBy('level')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->visible(fn (): bool => ! self::isTeacherUser())
                                ->live()
                                ->afterStateUpdated(fn ($state, Get $get, Set $set) => self::refreshScoreRows($get, $set)),
                            Select::make('class_section_id')
                                ->label('Arm')
                                ->options(fn (Get $get): array => ClassSection::query()
                                    ->when($get('school_class_id'), fn ($query, $classId) => $query->where('school_class_id', $classId))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->visible(fn (): bool => ! self::isTeacherUser())
                                ->live()
                                ->afterStateUpdated(fn ($state, Get $get, Set $set) => self::refreshScoreRows($get, $set)),
                            Hidden::make('subject_id')
                                ->visible(fn (): bool => self::isTeacherUser()),
                            Hidden::make('school_class_id')
                                ->visible(fn (): bool => self::isTeacherUser()),
                            Hidden::make('class_section_id')
                                ->visible(fn (): bool => self::isTeacherUser()),
                        ])
                        ->columns(3),
                    Repeater::make('scores')
                        ->label('Score sheet')
                        ->helperText('Scores are validated against the selected component maximum.')
                        ->schema([
                            Hidden::make('student_id'),
                            Hidden::make('owner_staff_id'),
                            TextInput::make('student')
                                ->label('Student')
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('score')
                                ->label('Score')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(fn (Get $get): float => (float) (AssessmentComponent::query()->find($get('../../assessment_component_id'))?->max_score ?? 100))
                                ->disabled(fn (Get $get): bool => self::isLockedScore($get('owner_staff_id')))
                                ->required(),
                            Textarea::make('remarks')
                                ->rows(1)
                                ->disabled(fn (Get $get): bool => self::isLockedScore($get('owner_staff_id'))),
                        ])
                        ->columns(3)
                        ->columnSpanFull()
                        ->addable(false)
                        ->deletable(true)
                        ->reorderable(false),
                ])
                ->action(function (array $data): void {
                    $saved = 0;

                    DB::transaction(function () use ($data, &$saved): void {
                        foreach ($data['scores'] ?? [] as $scoreRow) {
                            if (blank($scoreRow['student_id'] ?? null) || blank($scoreRow['score'] ?? null)) {
                                continue;
                            }

                            if (self::isLockedScore($scoreRow['owner_staff_id'] ?? null)) {
                                continue;
                            }

                            StudentScore::query()->updateOrCreate(
                                [
                                    'assessment_component_id' => $data['assessment_component_id'],
                                    'student_id' => $scoreRow['student_id'],
                                    'subject_id' => $data['subject_id'],
                                ],
                                [
                                    'school_id' => Filament::getTenant()?->getKey(),
                                    'exam_id' => $data['exam_id'],
                                    'staff_id' => self::currentStaff()?->getKey(),
                                    'score' => $scoreRow['score'],
                                    'status' => $data['status'],
                                    'remarks' => $scoreRow['remarks'] ?? null,
                                ],
                            );

                            $saved++;
                        }
                    });

                    if (($data['status'] ?? null) === 'submitted' && $saved > 0) {
                        self::compileSubmittedExam((int) $data['exam_id']);
                    }

                    Notification::make()
                        ->title("{$saved} score record(s) saved")
                        ->body(($data['status'] ?? null) === 'submitted'
                            ? 'Class results have been prepared for review.'
                            : 'Draft scores are saved. Submit them when they are ready for result review.')
                        ->success()
                        ->send();
                }),
            Action::make('submitDraftScores')
                ->label('Submit Draft Scores')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->modalHeading('Submit draft scores')
                ->modalDescription('Move saved draft scores to submitted so they can be compiled into results.')
                ->modalSubmitActionLabel('Submit drafts')
                ->schema([
                    Section::make('Submission scope')
                        ->schema([
                            Select::make('exam_id')
                                ->label('Exam')
                                ->options(fn (): array => Exam::query()
                                    ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                    ->orderByDesc('created_at')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live(),
                            Select::make('assessment_component_id')
                                ->label('Component')
                                ->options(fn (Get $get): array => AssessmentComponent::query()
                                    ->when($get('exam_id'), fn ($query, $examId) => $query->where('exam_id', $examId))
                                    ->where('is_active', true)
                                    ->orderBy('position')
                                    ->get()
                                    ->mapWithKeys(fn (AssessmentComponent $component): array => [
                                        $component->getKey() => "{$component->name} ({$component->max_score} marks)",
                                    ])
                                    ->all())
                                ->searchable()
                                ->preload(),
                            Select::make('class_subject_ids')
                                ->label('Subject / class')
                                ->options(fn (): array => self::teacherClassSubjectOptions())
                                ->searchable()
                                ->preload()
                                ->multiple()
                                ->visible(fn (): bool => self::isTeacherUser()),
                            Select::make('subject_id')
                                ->label('Subject')
                                ->options(fn (): array => Subject::query()
                                    ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->visible(fn (): bool => ! self::isTeacherUser()),
                            Select::make('school_class_id')
                                ->label('Class')
                                ->options(fn (): array => SchoolClass::query()
                                    ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                    ->orderBy('level')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->visible(fn (): bool => ! self::isTeacherUser()),
                            Select::make('class_section_id')
                                ->label('Arm')
                                ->options(fn (Get $get): array => ClassSection::query()
                                    ->when($get('school_class_id'), fn ($query, $classId) => $query->where('school_class_id', $classId))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->visible(fn (): bool => ! self::isTeacherUser()),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data): void {
                    $submitted = self::submitDraftScores($data);

                    if ($submitted > 0) {
                        self::compileSubmittedExam((int) $data['exam_id']);
                    }

                    Notification::make()
                        ->title("{$submitted} draft score(s) submitted")
                        ->body('Class results have been prepared for teacher and principal review.')
                        ->success()
                        ->send();
                }),
            Action::make('compileResults')
                ->label('Compile Results')
                ->icon('heroicon-o-calculator')
                ->color('primary')
                ->visible(fn (): bool => ! self::isTeacherUser())
                ->modalHeading('Compile student results')
                ->modalDescription('Use this after scores have been submitted. It creates or updates student report cards for PDF download.')
                ->modalSubmitActionLabel('Compile results')
                ->schema([
                    Select::make('exam_id')
                        ->label('Exam')
                        ->options(fn (): array => Exam::query()
                            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                            ->orderByDesc('created_at')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('status')
                        ->label('Score status to include')
                        ->default('submitted')
                        ->required()
                        ->options([
                            'submitted' => 'Submitted scores',
                            'approved' => 'Approved scores only',
                            'draft' => 'Draft, submitted, and approved scores',
                        ]),
                    Checkbox::make('create_report_cards')
                        ->label('Create/update report cards')
                        ->default(true),
                ])
                ->action(fn (array $data) => ListCompiledResults::compile($data)),
        ];
    }

    protected static function currentStaff(): ?Staff
    {
        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();

        if (! $user || ! $tenant) {
            return null;
        }

        return Staff::query()
            ->where('school_id', $tenant->getKey())
            ->where('user_id', $user->getKey())
            ->first();
    }

    /**
     * @return array<int, int>
     */
    protected static function teacherSubjectIds(?int $examId): array
    {
        $staff = self::currentStaff();

        if (! self::isTeacherUser()) {
            return [];
        }

        if (! $staff) {
            return [0];
        }

        $subjectIds = ClassSubject::query()
            ->where('school_id', $staff->school_id)
            ->where('staff_id', $staff->getKey())
            ->where('is_active', true)
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $subjectIds ?: [0];
    }

    /**
     * @return array<int, int>
     */
    protected static function teacherClassIds(?int $examId, ?int $subjectId): array
    {
        $staff = self::currentStaff();

        if (! self::isTeacherUser()) {
            return [];
        }

        if (! $staff || ! $subjectId) {
            return [0];
        }

        $classIds = ClassSubject::query()
            ->where('school_id', $staff->school_id)
            ->where('staff_id', $staff->getKey())
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->pluck('school_class_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $classIds ?: [0];
    }

    protected static function isTeacherUser(): bool
    {
        return (bool) Filament::auth()->user()?->hasSchoolRole(Filament::getTenant(), 'teacher');
    }

    protected static function isLockedScore(mixed $ownerStaffId): bool
    {
        if (! self::isTeacherUser() || blank($ownerStaffId)) {
            return false;
        }

        return (int) $ownerStaffId !== (int) self::currentStaff()?->getKey();
    }

    protected static function submitDraftScores(array $data): int
    {
        $tenant = Filament::getTenant();
        $staff = self::currentStaff();
        $classSubjectScopes = collect($data['class_subject_ids'] ?? [])
            ->filter()
            ->map(fn ($value): array => self::parseClassSubjectValue($value))
            ->values();
        $classSubjectIds = $classSubjectScopes->pluck(0)->filter()->unique()->values();
        $classSubjects = $classSubjectIds->isNotEmpty()
            ? ClassSubject::query()->whereIn('id', $classSubjectIds)->get()->keyBy('id')
            : collect();

        $query = StudentScore::query()
            ->where('school_id', $tenant?->getKey())
            ->where('exam_id', $data['exam_id'])
            ->where('status', 'draft')
            ->when($data['assessment_component_id'] ?? null, fn ($query, $componentId) => $query->where('assessment_component_id', $componentId));

        if (self::isTeacherUser()) {
            $query->where('staff_id', $staff?->getKey() ?: 0);

            if ($classSubjectScopes->isNotEmpty()) {
                $query->where(function ($query) use ($classSubjectScopes, $classSubjects, $tenant): void {
                    foreach ($classSubjectScopes as [$classSubjectId, $armId]) {
                        $classSubject = $classSubjects->get($classSubjectId);

                        if (! $classSubject) {
                            continue;
                        }

                        $query->orWhere(function ($query) use ($classSubject, $tenant, $armId): void {
                            $query
                                ->where('subject_id', $classSubject->subject_id)
                                ->whereHas('student.enrollments', fn ($query) => $query
                                    ->where('school_id', $tenant?->getKey())
                                    ->where('school_class_id', $classSubject->school_class_id)
                                    ->when($armId, fn ($query, $armId) => $query->where('class_section_id', $armId))
                                    ->where('status', 'active'));
                        });
                    }
                });
            }
        } else {
            $query
                ->when($data['subject_id'] ?? null, fn ($query, $subjectId) => $query->where('subject_id', $subjectId))
                ->when($data['school_class_id'] ?? null, fn ($query, $classId) => $query->whereHas('student.enrollments', fn ($query) => $query->where('school_class_id', $classId)))
                ->when($data['class_section_id'] ?? null, fn ($query, $armId) => $query->whereHas('student.enrollments', fn ($query) => $query->where('class_section_id', $armId)));
        }

        return $query->update(['status' => 'submitted']);
    }

    protected static function compileSubmittedExam(int $examId): void
    {
        ListCompiledResults::compile([
            'exam_id' => $examId,
            'status' => 'submitted',
            'create_report_cards' => true,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected static function teacherClassSubjectOptions(): array
    {
        $staff = self::currentStaff();

        if (! $staff) {
            return [];
        }

        $classSubjects = ClassSubject::query()
            ->with(['schoolClass', 'subject', 'staff'])
            ->where('school_id', $staff->school_id)
            ->where('is_active', true)
            ->where('staff_id', $staff->getKey())
            ->orderBy('school_class_id')
            ->get()
            ->keyBy('id');

        $options = [];

        $subjectTeachingKeys = collect();

        TeachingAssignment::query()
            ->with(['schoolClass', 'classSection', 'subject'])
            ->where('school_id', $staff->school_id)
            ->where('staff_id', $staff->getKey())
            ->where('assignment_role', TeachingAssignment::ROLE_SUBJECT_TEACHER)
            ->where('is_active', true)
            ->orderBy('school_class_id')
            ->get()
            ->each(function (TeachingAssignment $assignment) use (&$options, $classSubjects, $subjectTeachingKeys): void {
                $classSubject = $classSubjects
                    ->first(fn (ClassSubject $classSubject): bool => (int) $classSubject->school_class_id === (int) $assignment->school_class_id
                        && (int) $classSubject->subject_id === (int) $assignment->subject_id);

                if (! $classSubject) {
                    return;
                }

                $subjectTeachingKeys->push($classSubject->getKey());

                $armLabel = $assignment->classSection ? ' '.$assignment->classSection->name : '';
                $armId = $assignment->class_section_id ?: 0;

                $options[$classSubject->getKey().':'.$armId] = "{$assignment->subject?->name} - {$assignment->schoolClass?->name}{$armLabel} (My subject)";
            });

        $classSubjects
            ->each(function (ClassSubject $classSubject) use (&$options, $subjectTeachingKeys): void {
                if ($subjectTeachingKeys->contains($classSubject->getKey())) {
                    return;
                }

                $options[$classSubject->getKey().':0'] ??= "{$classSubject->subject?->name} - {$classSubject->schoolClass?->name} (My subject)";
            });

        return $options;
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    protected static function parseClassSubjectValue(mixed $value): array
    {
        if (blank($value)) {
            return [null, null];
        }

        [$classSubjectId, $armId] = array_pad(explode(':', (string) $value, 2), 2, null);

        return [
            filled($classSubjectId) ? (int) $classSubjectId : null,
            filled($armId) ? (int) $armId : null,
        ];
    }

    protected static function defaultTeacherArmId(?ClassSubject $classSubject): ?int
    {
        if (! $classSubject) {
            return null;
        }

        return TeacherWorkspace::formAssignments()
            ->first(fn ($assignment): bool => (int) $assignment->school_class_id === (int) $classSubject->school_class_id)
            ?->class_section_id;
    }

    protected static function refreshScoreRows(Get $get, Set $set): void
    {
        $examId = $get('exam_id');
        $componentId = $get('assessment_component_id');
        $subjectId = $get('subject_id');
        $classId = $get('school_class_id');

        if (! $examId || ! $componentId || ! $subjectId || ! $classId) {
            $set('scores', []);

            return;
        }

        $exam = Exam::query()->find($examId);

        $students = Enrollment::query()
            ->with('student')
            ->where('school_id', Filament::getTenant()?->getKey())
            ->where('academic_year_id', $exam?->academic_year_id)
            ->when($exam?->term_id, fn ($query, $termId) => $query->where('term_id', $termId))
            ->where('school_class_id', $classId)
            ->when($get('class_section_id'), fn ($query, $sectionId) => $query->where('class_section_id', $sectionId))
            ->where('status', 'active')
            ->get()
            ->pluck('student')
            ->filter();

        $existingScores = StudentScore::query()
            ->where('assessment_component_id', $componentId)
            ->where('subject_id', $subjectId)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $set('scores', $students
            ->sortBy('last_name')
            ->map(fn ($student): array => [
                'student_id' => $student->getKey(),
                'owner_staff_id' => $existingScores->get($student->getKey())?->staff_id,
                'student' => "{$student->full_name} ({$student->admission_number})",
                'score' => $existingScores->get($student->getKey())?->score,
                'remarks' => $existingScores->get($student->getKey())?->remarks,
            ])
            ->values()
            ->all());
    }

    public function getTabs(): array
    {
        return ClassTabs::studentEnrollment(StudentScore::class, 'All scores');
    }
}
