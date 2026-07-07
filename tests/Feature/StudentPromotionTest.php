<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Support\StudentPromotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPromotionTest extends TestCase
{
    use RefreshDatabase;

    protected function activeEnrollment(): Enrollment
    {
        $this->seed();

        return Enrollment::query()->where('status', 'active')->firstOrFail();
    }

    protected function nextSession(Enrollment $enrollment): array
    {
        $year = AcademicYear::query()->create([
            'school_id' => $enrollment->school_id,
            'name' => '2027/2028',
            'starts_on' => '2027-09-01',
            'ends_on' => '2028-07-31',
            'is_current' => false,
            'is_active' => true,
        ]);

        $term = Term::query()->create([
            'school_id' => $enrollment->school_id,
            'academic_year_id' => $year->getKey(),
            'name' => 'First Term',
            'starts_on' => '2027-09-01',
            'ends_on' => '2027-12-15',
            'is_current' => false,
            'is_active' => true,
        ]);

        $targetClass = SchoolClass::query()->create([
            'school_id' => $enrollment->school_id,
            'name' => 'Promotion Target Class',
            'code' => 'PTC-1',
            'level' => 99,
            'is_active' => true,
        ]);

        return [$year, $term, $targetClass];
    }

    public function test_students_can_be_promoted_to_a_new_class(): void
    {
        $enrollment = $this->activeEnrollment();
        [$year, $term, $targetClass] = $this->nextSession($enrollment);

        $result = StudentPromotion::apply(collect([$enrollment]), [
            'outcome' => StudentPromotion::OUTCOME_PROMOTED,
            'target_academic_year_id' => $year->getKey(),
            'target_term_id' => $term->getKey(),
            'target_class_id' => $targetClass->getKey(),
        ]);

        $this->assertSame(1, $result['moved']);
        $this->assertSame('promoted', $enrollment->fresh()->status);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $enrollment->student_id,
            'academic_year_id' => $year->getKey(),
            'school_class_id' => $targetClass->getKey(),
            'status' => 'active',
        ]);
    }

    public function test_promotion_skips_students_already_placed_in_target_session(): void
    {
        $enrollment = $this->activeEnrollment();
        [$year, $term, $targetClass] = $this->nextSession($enrollment);

        $data = [
            'outcome' => StudentPromotion::OUTCOME_PROMOTED,
            'target_academic_year_id' => $year->getKey(),
            'target_term_id' => $term->getKey(),
            'target_class_id' => $targetClass->getKey(),
        ];

        StudentPromotion::apply(collect([$enrollment]), $data);
        $secondRun = StudentPromotion::apply(collect([$enrollment->fresh()]), $data);

        $this->assertSame(0, $secondRun['moved']);
        $this->assertSame(1, $secondRun['skipped']);
    }

    public function test_repeating_keeps_the_same_class_in_the_new_session(): void
    {
        $enrollment = $this->activeEnrollment();
        [$year, $term] = $this->nextSession($enrollment);

        $result = StudentPromotion::apply(collect([$enrollment]), [
            'outcome' => StudentPromotion::OUTCOME_REPEATED,
            'target_academic_year_id' => $year->getKey(),
            'target_term_id' => $term->getKey(),
        ]);

        $this->assertSame(1, $result['moved']);
        $this->assertSame('repeated', $enrollment->fresh()->status);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $enrollment->student_id,
            'academic_year_id' => $year->getKey(),
            'school_class_id' => $enrollment->school_class_id,
            'status' => 'active',
        ]);
    }

    public function test_graduating_marks_student_and_enrollment_as_graduated(): void
    {
        $enrollment = $this->activeEnrollment();

        $result = StudentPromotion::apply(collect([$enrollment]), [
            'outcome' => StudentPromotion::OUTCOME_GRADUATED,
        ]);

        $this->assertSame(1, $result['moved']);
        $this->assertSame('graduated', $enrollment->fresh()->status);
        $this->assertSame('graduated', $enrollment->student->fresh()->status);
    }
}
