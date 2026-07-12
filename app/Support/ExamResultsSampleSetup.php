<?php

namespace App\Support;

use App\Filament\Resources\CompiledResults\Pages\ListCompiledResults;
use App\Models\AcademicYear;
use App\Models\AssessmentComponent;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\GradeScale;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\Term;
use Illuminate\Support\Facades\DB;

/**
 * Generates a realistic exam, continuous-assessment + main-exam scores for
 * every enrolled student against their class's compulsory subjects, then
 * compiles and publishes report cards using the same production logic the
 * exam officer flow uses (ListCompiledResults::compile), so sample data
 * behaves identically to a real result set.
 */
class ExamResultsSampleSetup
{
    /**
     * @return array{students:int, scores:int, reportCards:int}
     */
    public static function createForSchool(School $school): array
    {
        return DB::transaction(function () use ($school): array {
            $academicYear = AcademicYear::query()
                ->where('school_id', $school->getKey())
                ->where('is_current', true)
                ->first();

            $term = Term::query()
                ->where('school_id', $school->getKey())
                ->where('academic_year_id', $academicYear?->getKey())
                ->where('is_current', true)
                ->first();

            if (! $academicYear || ! $term) {
                return ['students' => 0, 'scores' => 0, 'reportCards' => 0];
            }

            $enrollments = Enrollment::query()
                ->with('student')
                ->where('school_id', $school->getKey())
                ->where('academic_year_id', $academicYear->getKey())
                ->where('status', 'active')
                ->get()
                ->filter(fn (Enrollment $enrollment): bool => $enrollment->student instanceof Student);

            if ($enrollments->isEmpty()) {
                return ['students' => 0, 'scores' => 0, 'reportCards' => 0];
            }

            $classSubjectsByClass = ClassSubject::query()
                ->where('school_id', $school->getKey())
                ->where('is_compulsory', true)
                ->where('is_active', true)
                ->get()
                ->groupBy('school_class_id');

            if ($classSubjectsByClass->isEmpty()) {
                return ['students' => 0, 'scores' => 0, 'reportCards' => 0];
            }

            self::ensureGradeScale($school);

            $exam = Exam::query()->updateOrCreate(
                [
                    'school_id' => $school->getKey(),
                    'academic_year_id' => $academicYear->getKey(),
                    'term_id' => $term->getKey(),
                    'name' => $term->name.' Examination',
                ],
                [
                    'type' => 'term',
                    'starts_on' => $term->ends_on?->copy()->subWeeks(2) ?? now(),
                    'ends_on' => $term->ends_on ?? now(),
                    'status' => 'open',
                    'remarks' => 'Sample examination for onboarding demonstration.',
                ],
            );

            $ca = AssessmentComponent::query()->updateOrCreate(
                ['exam_id' => $exam->getKey(), 'code' => 'CA'],
                ['school_id' => $school->getKey(), 'name' => 'Continuous Assessment', 'max_score' => 40, 'position' => 1, 'is_active' => true],
            );

            $mainExam = AssessmentComponent::query()->updateOrCreate(
                ['exam_id' => $exam->getKey(), 'code' => 'EXAM'],
                ['school_id' => $school->getKey(), 'name' => 'Main Examination', 'max_score' => 60, 'position' => 2, 'is_active' => true],
            );

            $studentsScored = collect();
            $scoreCount = 0;

            foreach ($enrollments as $enrollment) {
                $classSubjects = $classSubjectsByClass->get($enrollment->school_class_id);

                if (! $classSubjects || $classSubjects->isEmpty()) {
                    continue;
                }

                $student = $enrollment->student;
                $studentsScored->push($student->getKey());

                foreach ($classSubjects as $classSubject) {
                    StudentScore::query()->updateOrCreate(
                        [
                            'assessment_component_id' => $ca->getKey(),
                            'student_id' => $student->getKey(),
                            'subject_id' => $classSubject->subject_id,
                        ],
                        [
                            'school_id' => $school->getKey(),
                            'exam_id' => $exam->getKey(),
                            'staff_id' => $classSubject->staff_id,
                            'score' => self::scoreFor($student->getKey(), $classSubject->subject_id, 40),
                            'status' => 'approved',
                        ],
                    );

                    StudentScore::query()->updateOrCreate(
                        [
                            'assessment_component_id' => $mainExam->getKey(),
                            'student_id' => $student->getKey(),
                            'subject_id' => $classSubject->subject_id,
                        ],
                        [
                            'school_id' => $school->getKey(),
                            'exam_id' => $exam->getKey(),
                            'staff_id' => $classSubject->staff_id,
                            'score' => self::scoreFor($student->getKey(), $classSubject->subject_id, 60),
                            'status' => 'approved',
                        ],
                    );

                    $scoreCount += 2;
                }
            }

            if ($studentsScored->isEmpty()) {
                return ['students' => 0, 'scores' => 0, 'reportCards' => 0];
            }

            ListCompiledResults::compile([
                'exam_id' => $exam->getKey(),
                'status' => 'approved',
                'create_report_cards' => true,
            ]);

            $publishedCount = self::publishReportCards($exam);

            return [
                'students' => $studentsScored->unique()->count(),
                'scores' => $scoreCount,
                'reportCards' => $publishedCount,
            ];
        });
    }

    protected static function ensureGradeScale(School $school): void
    {
        if (GradeScale::query()->where('school_id', $school->getKey())->where('is_active', true)->exists()) {
            return;
        }

        $isSecondary = $school->division === School::DIVISION_SECONDARY;

        $grades = $isSecondary
            ? [
                ['grade' => 'A1', 'min_score' => 75, 'max_score' => 100, 'grade_point' => 9, 'remark' => 'Excellent'],
                ['grade' => 'B2', 'min_score' => 70, 'max_score' => 74.99, 'grade_point' => 8, 'remark' => 'Very Good'],
                ['grade' => 'B3', 'min_score' => 65, 'max_score' => 69.99, 'grade_point' => 7, 'remark' => 'Good'],
                ['grade' => 'C4', 'min_score' => 60, 'max_score' => 64.99, 'grade_point' => 6, 'remark' => 'Credit'],
                ['grade' => 'C5', 'min_score' => 55, 'max_score' => 59.99, 'grade_point' => 5, 'remark' => 'Credit'],
                ['grade' => 'C6', 'min_score' => 50, 'max_score' => 54.99, 'grade_point' => 4, 'remark' => 'Credit'],
                ['grade' => 'D7', 'min_score' => 45, 'max_score' => 49.99, 'grade_point' => 3, 'remark' => 'Pass'],
                ['grade' => 'E8', 'min_score' => 40, 'max_score' => 44.99, 'grade_point' => 2, 'remark' => 'Pass'],
                ['grade' => 'F9', 'min_score' => 0, 'max_score' => 39.99, 'grade_point' => 1, 'remark' => 'Fail'],
            ]
            : [
                ['grade' => 'A', 'min_score' => 80, 'max_score' => 100, 'grade_point' => 5, 'remark' => 'Excellent'],
                ['grade' => 'B', 'min_score' => 70, 'max_score' => 79.99, 'grade_point' => 4, 'remark' => 'Very Good'],
                ['grade' => 'C', 'min_score' => 60, 'max_score' => 69.99, 'grade_point' => 3, 'remark' => 'Good'],
                ['grade' => 'D', 'min_score' => 50, 'max_score' => 59.99, 'grade_point' => 2, 'remark' => 'Needs Improvement'],
                ['grade' => 'E', 'min_score' => 0, 'max_score' => 49.99, 'grade_point' => 1, 'remark' => 'Needs Support'],
            ];

        foreach ($grades as $grade) {
            GradeScale::query()->updateOrCreate(
                ['school_id' => $school->getKey(), 'name' => 'Default', 'grade' => $grade['grade']],
                $grade + ['is_active' => true],
            );
        }
    }

    /**
     * Every student gets a stable baseline performance level (as a real
     * student tends to perform similarly across subjects), plus a small
     * per-subject variation so not every subject score is identical.
     * Deterministic on (student, subject) so re-running the button is safe.
     */
    protected static function scoreFor(int $studentId, int $subjectId, int $maxScore): int
    {
        $ratio = self::performanceRatio($studentId) + self::subjectJitter($studentId, $subjectId);
        $ratio = max(0.05, min(0.97, $ratio));

        return (int) round($maxScore * $ratio);
    }

    protected static function performanceRatio(int $studentId): float
    {
        $seed = crc32('performance-'.$studentId) % 1000;

        return match (true) {
            $seed < 100 => 0.85 + (($seed % 100) / 100) * 0.10,
            $seed < 350 => 0.65 + (($seed % 100) / 100) * 0.19,
            $seed < 700 => 0.50 + (($seed % 100) / 100) * 0.14,
            $seed < 900 => 0.35 + (($seed % 100) / 100) * 0.14,
            default => 0.15 + (($seed % 100) / 100) * 0.18,
        };
    }

    protected static function subjectJitter(int $studentId, int $subjectId): float
    {
        $seed = crc32($studentId.'-'.$subjectId.'-jitter') % 21;

        return ($seed - 10) / 100;
    }

    protected static function publishReportCards(Exam $exam): int
    {
        $reportCards = ReportCard::query()
            ->where('exam_id', $exam->getKey())
            ->get();

        foreach ($reportCards as $reportCard) {
            $percentage = $reportCard->average_score !== null
                ? min(100, max(0, (float) $reportCard->average_score))
                : 0.0;

            $reportCard->forceFill([
                'status' => 'published',
                'published_at' => now(),
                'teacher_comment' => self::teacherComment($percentage),
                'principal_comment' => self::principalComment($percentage),
            ])->save();

            ResultTraitSampleSetup::rateReportCard($reportCard->fresh('school'));
        }

        return $reportCards->count();
    }

    protected static function teacherComment(float $percentage): string
    {
        return match (true) {
            $percentage >= 80 => 'An outstanding term. Consistently attentive in class and submits assignments early.',
            $percentage >= 65 => 'A hardworking pupil who participates well in class discussions. Keep it up.',
            $percentage >= 50 => 'Shows steady improvement this term. Needs to pay closer attention during lessons.',
            $percentage >= 40 => 'Capable of doing much better with more consistent revision at home.',
            default => 'Needs significant improvement. Parents are encouraged to support home study.',
        };
    }

    protected static function principalComment(float $percentage): string
    {
        return match (true) {
            $percentage >= 80 => 'Excellent result. A commendable performance this term.',
            $percentage >= 65 => 'Good result. Keep up the good work.',
            $percentage >= 50 => 'Satisfactory result. Encouraged to work harder next term.',
            $percentage >= 40 => 'Fair result. More effort is required next term.',
            default => 'Below expectation. Requires close monitoring next term.',
        };
    }
}
