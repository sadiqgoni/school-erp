<?php

namespace Tests\Feature;

use App\Filament\Resources\CompiledResults\Pages\ListCompiledResults;
use App\Models\AssessmentComponent;
use App\Models\CompiledResult;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ReportCard;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\ParentAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultsCompilationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seeds two classes writing the same subject in one exam with known scores.
     *
     * @return array{exam: Exam, subject: Subject, jss1: array<int, Student>, jss3: array<int, Student>}
     */
    protected function examAcrossTwoClasses(): array
    {
        $this->seed();

        $exam = Exam::query()->firstOrFail();
        $subject = Subject::query()->where('code', 'MTH')->firstOrFail();
        $jssOne = SchoolClass::query()->where('code', 'JSS1')->firstOrFail();
        $jssThree = SchoolClass::query()->where('code', 'JSS3')->firstOrFail();
        $term = Term::query()->where('is_current', true)->firstOrFail();

        $component = AssessmentComponent::query()
            ->where('exam_id', $exam->getKey())
            ->firstOr(fn () => AssessmentComponent::query()->create([
                'school_id' => $exam->school_id,
                'exam_id' => $exam->getKey(),
                'name' => 'Exam',
                'max_score' => 100,
                'position' => 1,
                'is_active' => true,
            ]));

        $makeStudent = function (string $first, SchoolClass $class, float $score) use ($exam, $subject, $term, $component): Student {
            $student = Student::query()->create([
                'school_id' => $exam->school_id,
                'admission_number' => 'RES-'.strtoupper($first),
                'first_name' => $first,
                'last_name' => 'Resultson',
                'gender' => 'female',
                'status' => 'active',
            ]);

            Enrollment::query()->create([
                'school_id' => $exam->school_id,
                'student_id' => $student->getKey(),
                'academic_year_id' => $exam->academic_year_id,
                'term_id' => $term->getKey(),
                'school_class_id' => $class->getKey(),
                'enrolled_on' => today(),
                'status' => 'active',
            ]);

            StudentScore::query()->create([
                'school_id' => $exam->school_id,
                'exam_id' => $exam->getKey(),
                'assessment_component_id' => $component->getKey(),
                'student_id' => $student->getKey(),
                'subject_id' => $subject->getKey(),
                'score' => $score,
                'status' => 'submitted',
            ]);

            return $student;
        };

        // JSS1: 90 and 40. JSS3: 70, plus a tie pair at 55.
        $jss1 = [
            $makeStudent('Amina', $jssOne, 90),
            $makeStudent('Binta', $jssOne, 40),
        ];

        $jss3 = [
            $makeStudent('Chidi', $jssThree, 70),
            $makeStudent('Dada', $jssThree, 55),
            $makeStudent('Efe', $jssThree, 55),
        ];

        ListCompiledResults::compile([
            'exam_id' => $exam->getKey(),
            'status' => 'submitted',
            'create_report_cards' => true,
        ]);

        return ['exam' => $exam, 'subject' => $subject, 'jss1' => $jss1, 'jss3' => $jss3];
    }

    protected function positionFor(Exam $exam, Subject $subject, Student $student): ?int
    {
        return CompiledResult::query()
            ->where('exam_id', $exam->getKey())
            ->where('subject_id', $subject->getKey())
            ->where('student_id', $student->getKey())
            ->value('position');
    }

    public function test_subject_positions_are_ranked_within_each_class_not_school_wide(): void
    {
        ['exam' => $exam, 'subject' => $subject, 'jss1' => $jss1, 'jss3' => $jss3] = $this->examAcrossTwoClasses();

        // JSS1: 90 → 1st, 40 → 2nd (not mixed with JSS3 scores).
        $this->assertSame(1, $this->positionFor($exam, $subject, $jss1[0]));
        $this->assertSame(2, $this->positionFor($exam, $subject, $jss1[1]));

        // JSS3: 70 → 1st even though a JSS1 student scored 90.
        $this->assertSame(1, $this->positionFor($exam, $subject, $jss3[0]));
    }

    public function test_tied_scores_share_a_joint_position(): void
    {
        ['exam' => $exam, 'subject' => $subject, 'jss3' => $jss3] = $this->examAcrossTwoClasses();

        // Both 55s are joint 2nd in JSS3.
        $this->assertSame(2, $this->positionFor($exam, $subject, $jss3[1]));
        $this->assertSame(2, $this->positionFor($exam, $subject, $jss3[2]));
    }

    public function test_compile_creates_report_cards_with_class_positions(): void
    {
        ['exam' => $exam, 'jss1' => $jss1] = $this->examAcrossTwoClasses();

        $reportCard = ReportCard::query()
            ->where('exam_id', $exam->getKey())
            ->where('student_id', $jss1[0]->getKey())
            ->firstOrFail();

        $this->assertSame(1, (int) $reportCard->position);
        $this->assertSame('draft', $reportCard->status);
        $this->assertEqualsWithDelta(90.0, (float) $reportCard->average_score, 0.01);
    }

    public function test_parent_cannot_download_unpublished_report_card_pdf(): void
    {
        $this->seed();
        $this->seed(ParentAccountsSeeder::class);

        $parent = User::query()->where('email', 'guardian@example.com')->firstOrFail();

        $child = Student::query()
            ->whereHas('guardianLinks.guardian', fn ($query) => $query->where('user_id', $parent->getKey()))
            ->firstOrFail();

        $exam = Exam::query()->firstOrFail();

        $reportCard = ReportCard::query()->updateOrCreate(
            [
                'exam_id' => $exam->getKey(),
                'student_id' => $child->getKey(),
            ],
            [
                'school_id' => $exam->school_id,
                'academic_year_id' => $exam->academic_year_id,
                'term_id' => $exam->term_id,
                'total_score' => 80,
                'average_score' => 80,
                'status' => 'draft',
            ],
        );

        $this
            ->actingAs($parent)
            ->get(route('report-cards.pdf', $reportCard))
            ->assertForbidden();

        $reportCard->forceFill(['status' => 'published', 'published_at' => now()])->save();

        $this
            ->actingAs($parent)
            ->get(route('report-cards.pdf', $reportCard))
            ->assertOk();
    }
}
