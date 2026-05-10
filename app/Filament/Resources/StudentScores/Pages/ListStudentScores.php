<?php

namespace App\Filament\Resources\StudentScores\Pages;

use App\Filament\Resources\StudentScores\StudentScoreResource;
use App\Models\AssessmentComponent;
use App\Models\ClassSection;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\StudentScore;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use Filament\Actions\Action;
use Filament\Facades\Filament;
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
                    Section::make('Class and subject')
                        ->schema([
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
                                ->live()
                                ->afterStateUpdated(fn ($state, Get $get, Set $set) => self::refreshScoreRows($get, $set)),
                        ])
                        ->columns(3),
                    Repeater::make('scores')
                        ->label('Score sheet')
                        ->helperText('Scores are validated against the selected component maximum.')
                        ->schema([
                            Hidden::make('student_id'),
                            TextInput::make('student')
                                ->label('Student')
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('score')
                                ->label('Score')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(fn (Get $get): float => (float) (AssessmentComponent::query()->find($get('../../assessment_component_id'))?->max_score ?? 100))
                                ->required(),
                            Textarea::make('remarks')
                                ->rows(1),
                        ])
                        ->columns(3)
                        ->columnSpanFull()
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ])
                ->action(function (array $data): void {
                    $saved = 0;

                    DB::transaction(function () use ($data, &$saved): void {
                        foreach ($data['scores'] ?? [] as $scoreRow) {
                            if (blank($scoreRow['student_id'] ?? null) || blank($scoreRow['score'] ?? null)) {
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

                    Notification::make()
                        ->title("{$saved} score record(s) saved")
                        ->success()
                        ->send();
                }),
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
        $exam = $examId ? Exam::query()->find($examId) : null;

        if (! $staff || ! $exam) {
            return [];
        }

        return TeachingAssignment::query()
            ->where('school_id', $staff->school_id)
            ->where('staff_id', $staff->getKey())
            ->where('academic_year_id', $exam->academic_year_id)
            ->when($exam->term_id, fn ($query, $termId) => $query->where('term_id', $termId))
            ->where('is_active', true)
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    protected static function teacherClassIds(?int $examId, ?int $subjectId): array
    {
        $staff = self::currentStaff();
        $exam = $examId ? Exam::query()->find($examId) : null;

        if (! $staff || ! $exam || ! $subjectId) {
            return [];
        }

        return TeachingAssignment::query()
            ->where('school_id', $staff->school_id)
            ->where('staff_id', $staff->getKey())
            ->where('academic_year_id', $exam->academic_year_id)
            ->when($exam->term_id, fn ($query, $termId) => $query->where('term_id', $termId))
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->pluck('school_class_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
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
                'student' => "{$student->full_name} ({$student->admission_number})",
                'score' => $existingScores->get($student->getKey())?->score,
                'remarks' => $existingScores->get($student->getKey())?->remarks,
            ])
            ->values()
            ->all());
    }
}
