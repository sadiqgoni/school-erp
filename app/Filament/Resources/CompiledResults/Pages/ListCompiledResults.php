<?php

namespace App\Filament\Resources\CompiledResults\Pages;

use App\Filament\Resources\CompiledResults\CompiledResultResource;
use App\Filament\Support\ClassTabs;
use App\Models\CompiledResult;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\GradeScale;
use App\Models\ReportCard;
use App\Models\StudentAttendanceRecord;
use App\Models\StudentScore;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListCompiledResults extends ListRecords
{
    protected static string $resource = CompiledResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('compileResults')
                ->label('Compile Results')
                ->icon('heroicon-o-calculator')
                ->color('primary')
                ->modalHeading('Compile exam results')
                ->modalDescription('Totals submitted scores, applies the school grade scale, ranks subject results, and prepares draft report cards.')
                ->modalSubmitActionLabel('Compile now')
                ->modalWidth('lg')
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
                            'submitted' => 'Submitted only',
                            'approved' => 'Approved only',
                            'draft' => 'Draft too',
                        ]),
                    Checkbox::make('create_report_cards')
                        ->label('Create/update report cards')
                        ->default(true),
                ])
                ->action(fn (array $data) => self::compile($data)),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::studentEnrollment(CompiledResult::class, 'All results');
    }

    public static function compile(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $exam = Exam::query()->with('components')->findOrFail($data['exam_id']);
            $schoolId = Filament::getTenant()?->getKey() ?? $exam->school_id;
            $allowedStatuses = match ($data['status']) {
                'approved' => ['approved'],
                'draft' => ['draft', 'submitted', 'approved'],
                default => ['submitted', 'approved'],
            };

            $scores = StudentScore::query()
                ->where('school_id', $schoolId)
                ->where('exam_id', $exam->getKey())
                ->whereIn('status', $allowedStatuses)
                ->get()
                ->groupBy(fn (StudentScore $score): string => $score->student_id.'-'.$score->subject_id);

            $compiled = collect();
            $gradeScales = GradeScale::query()
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->orderByDesc('min_score')
                ->get();

            foreach ($scores as $group) {
                $first = $group->first();
                $total = (float) $group->sum('score');
                $grade = self::resolveGrade($gradeScales, $total);

                $compiledResult = CompiledResult::query()->updateOrCreate(
                    [
                        'exam_id' => $exam->getKey(),
                        'student_id' => $first->student_id,
                        'subject_id' => $first->subject_id,
                    ],
                    [
                        'school_id' => $schoolId,
                        'total_score' => $total,
                        'grade' => $grade?->grade,
                        'grade_point' => $grade?->grade_point,
                        'remark' => $grade?->remark,
                        'status' => 'compiled',
                    ],
                );

                $compiled->push($compiledResult);
            }

            self::rankSubjects($exam);

            if ($data['create_report_cards'] ?? true) {
                self::createReportCards($exam, $compiled);
            }

            Notification::make()
                ->title('Results compiled')
                ->body($compiled->count().' subject result(s) were compiled for '.$exam->name.'.')
                ->success()
                ->send();
        });
    }

    /**
     * @param  Collection<int, GradeScale>  $gradeScales  active scales sorted by min_score desc
     */
    protected static function resolveGrade($gradeScales, float $score): ?GradeScale
    {
        return $gradeScales->first(fn (GradeScale $scale): bool => $score >= (float) $scale->min_score
            && $score <= (float) $scale->max_score);
    }

    /**
     * Rank each subject within the student's class/arm (not school-wide),
     * giving equal scores the same joint position.
     */
    protected static function rankSubjects(Exam $exam): void
    {
        $results = CompiledResult::query()
            ->where('exam_id', $exam->getKey())
            ->get();

        $placements = $results
            ->pluck('student_id')
            ->unique()
            ->mapWithKeys(fn ($studentId): array => [
                $studentId => self::placementKey(self::studentPlacement($exam, (int) $studentId)),
            ]);

        $results
            ->groupBy(fn (CompiledResult $result): string => $result->subject_id.'|'.$placements->get($result->student_id))
            ->each(function ($group): void {
                $previousScore = null;
                $position = 0;
                $index = 0;

                foreach ($group->sortByDesc(fn (CompiledResult $result): float => (float) $result->total_score) as $result) {
                    $index++;
                    $score = (float) $result->total_score;

                    if ($previousScore === null || $score < $previousScore) {
                        $position = $index;
                        $previousScore = $score;
                    }

                    $result->forceFill(['position' => $position])->save();
                }
            });
    }

    protected static function createReportCards(Exam $exam, $compiled): void
    {
        $totals = $compiled
            ->groupBy('student_id')
            ->map(fn ($results): array => [
                'total' => (float) $results->sum('total_score'),
                'average' => round((float) $results->avg('total_score'), 2),
                'subjects' => $results->count(),
            ]);

        $placements = $totals
            ->keys()
            ->mapWithKeys(fn ($studentId): array => [$studentId => self::studentPlacement($exam, (int) $studentId)]);
        $reportCards = collect();

        foreach ($totals as $studentId => $summary) {
            $attendance = self::attendanceSummary($exam, (int) $studentId);
            $reportCard = ReportCard::query()->firstOrNew(
                [
                    'exam_id' => $exam->getKey(),
                    'student_id' => $studentId,
                ],
            );

            $reportCard->fill([
                'school_id' => $exam->school_id,
                'academic_year_id' => $exam->academic_year_id,
                'term_id' => $exam->term_id,
                'total_score' => $summary['total'],
                'average_score' => $summary['average'],
                'position' => null,
                'attendance_total_days' => $attendance['total'],
                'attendance_present_days' => $attendance['present'],
                'attendance_absent_days' => $attendance['absent'],
                'status' => $reportCard->exists ? $reportCard->status : 'draft',
            ])->save();

            $reportCards->put($studentId, $reportCard);
        }

        $totals
            ->groupBy(
                fn (array $summary, $studentId): string => self::placementKey($placements->get($studentId)),
                preserveKeys: true,
            )
            ->each(function ($classTotals) use ($reportCards): void {
                $previousAverage = null;
                $position = 0;
                $index = 0;

                foreach ($classTotals->sortByDesc('average') as $studentId => $summary) {
                    $reportCard = $reportCards->get($studentId);

                    if (! $reportCard) {
                        continue;
                    }

                    $index++;

                    if ($previousAverage === null || $summary['average'] < $previousAverage) {
                        $position = $index;
                        $previousAverage = $summary['average'];
                    }

                    $reportCard->forceFill(['position' => $position])->save();
                }
            });
    }

    protected static function studentPlacement(Exam $exam, int $studentId): ?Enrollment
    {
        return Enrollment::query()
            ->where('school_id', $exam->school_id)
            ->where('student_id', $studentId)
            ->where('academic_year_id', $exam->academic_year_id)
            ->when($exam->term_id, fn ($query, $termId) => $query->where(fn ($query) => $query
                ->where('term_id', $termId)
                ->orWhereNull('term_id')))
            ->orderByRaw("case when status = 'active' then 0 else 1 end")
            ->orderByRaw('term_id is null')
            ->latest('enrolled_on')
            ->first();
    }

    protected static function placementKey(?Enrollment $placement): string
    {
        return collect([
            $placement?->school_class_id,
            $placement?->class_section_id ?: 'whole',
        ])->implode(':');
    }

    /**
     * @return array{total: int, present: int, absent: int}
     */
    protected static function attendanceSummary(Exam $exam, int $studentId): array
    {
        $records = StudentAttendanceRecord::query()
            ->where('student_id', $studentId)
            ->whereHas('studentAttendance', function ($query) use ($exam): void {
                $query
                    ->where('school_id', $exam->school_id)
                    ->where('academic_year_id', $exam->academic_year_id)
                    ->when($exam->term_id, fn ($query, $termId) => $query->where('term_id', $termId))
                    ->where('status', 'submitted');
            })
            ->get();

        $present = $records->whereIn('status', ['present', 'late'])->count();
        $absent = $records->where('status', 'absent')->count();

        return [
            'total' => $records->count(),
            'present' => $present,
            'absent' => $absent,
        ];
    }
}
