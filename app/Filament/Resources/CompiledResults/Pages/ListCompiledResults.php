<?php

namespace App\Filament\Resources\CompiledResults\Pages;

use App\Filament\Resources\CompiledResults\CompiledResultResource;
use App\Models\CompiledResult;
use App\Models\Exam;
use App\Models\GradeScale;
use App\Models\ReportCard;
use App\Models\StudentScore;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
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

    protected static function compile(array $data): void
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

            foreach ($scores as $group) {
                $first = $group->first();
                $total = (float) $group->sum('score');
                $grade = self::resolveGrade($schoolId, $total);

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

            self::rankSubjects($exam->getKey());

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

    protected static function resolveGrade(int $schoolId, float $score): ?GradeScale
    {
        return GradeScale::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderByDesc('min_score')
            ->first();
    }

    protected static function rankSubjects(int $examId): void
    {
        CompiledResult::query()
            ->where('exam_id', $examId)
            ->get()
            ->groupBy('subject_id')
            ->each(function ($results): void {
                $position = 1;

                $results
                    ->sortByDesc('total_score')
                    ->each(function (CompiledResult $result) use (&$position): void {
                        $result->forceFill(['position' => $position++])->save();
                    });
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

        $rankedStudentIds = $totals
            ->sortByDesc('average')
            ->keys()
            ->values();

        foreach ($totals as $studentId => $summary) {
            ReportCard::query()->updateOrCreate(
                [
                    'exam_id' => $exam->getKey(),
                    'student_id' => $studentId,
                ],
                [
                    'school_id' => $exam->school_id,
                    'academic_year_id' => $exam->academic_year_id,
                    'term_id' => $exam->term_id,
                    'total_score' => $summary['total'],
                    'average_score' => $summary['average'],
                    'position' => $rankedStudentIds->search($studentId) + 1,
                    'status' => 'draft',
                ],
            );
        }
    }
}
