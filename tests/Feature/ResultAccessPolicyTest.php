<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\Guardian;
use App\Models\GuardianStudent;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\Term;
use App\Models\User;
use App\Support\ResultAccessPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultAccessPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeReportCardWithBalance(bool $withholdPolicy, float $balance): array
    {
        // The very first User ever created in an empty test database becomes
        // platform superadmin automatically (first-boot bootstrapping) — seed
        // one first so the guardian created below is a normal account.
        $this->seed();

        $school = School::query()->create([
            'name' => 'Fee Gate School', 'code' => 'FEE-GATE', 'slug' => 'fee-gate-school',
            'division' => School::DIVISION_SECONDARY, 'email' => 'fee-gate@example.com', 'country' => 'Nigeria',
            'is_active' => true, 'withhold_results_for_debtors' => $withholdPolicy,
        ]);

        $academicYear = AcademicYear::query()->create([
            'school_id' => $school->getKey(), 'name' => '2026/2027',
            'starts_on' => '2026-09-01', 'ends_on' => '2027-07-31', 'is_current' => true, 'is_active' => true,
        ]);

        $term = Term::query()->create([
            'school_id' => $school->getKey(), 'academic_year_id' => $academicYear->getKey(),
            'name' => 'First Term', 'position' => 1, 'starts_on' => '2026-09-01', 'ends_on' => '2026-12-18',
            'is_current' => true, 'is_active' => true,
        ]);

        $student = Student::query()->create([
            'school_id' => $school->getKey(), 'admission_number' => 'FEE-GATE/001',
            'first_name' => 'Amina', 'last_name' => 'Test', 'gender' => 'female', 'status' => 'active',
        ]);

        $guardianUser = User::query()->create([
            'name' => 'Fee Gate Parent', 'email' => 'fee-gate-parent@example.com',
            'password' => bcrypt('secret'), 'is_active' => true,
        ]);
        $guardianUser->schools()->attach($school, ['role' => User::SCHOOL_ROLE_PARENT, 'is_primary' => false]);

        $guardian = Guardian::query()->create([
            'school_id' => $school->getKey(), 'user_id' => $guardianUser->getKey(),
            'name' => 'Fee Gate Parent', 'phone' => '+2348000000099', 'is_active' => true,
        ]);

        GuardianStudent::query()->create([
            'school_id' => $school->getKey(), 'guardian_id' => $guardian->getKey(), 'student_id' => $student->getKey(),
            'relationship' => 'mother', 'is_primary_contact' => true, 'can_pick_up' => true, 'receives_sms' => true,
        ]);

        if ($balance > 0) {
            StudentInvoice::query()->create([
                'school_id' => $school->getKey(), 'student_id' => $student->getKey(),
                'academic_year_id' => $academicYear->getKey(), 'term_id' => $term->getKey(),
                'invoice_date' => now(), 'subtotal' => $balance, 'total' => $balance,
                'amount_paid' => 0, 'balance' => $balance, 'status' => 'unpaid',
            ]);
        }

        $exam = Exam::query()->create([
            'school_id' => $school->getKey(), 'academic_year_id' => $academicYear->getKey(), 'term_id' => $term->getKey(),
            'name' => 'First Term Exam', 'type' => 'term', 'status' => 'open',
        ]);

        $reportCard = ReportCard::query()->create([
            'school_id' => $school->getKey(), 'exam_id' => $exam->getKey(), 'student_id' => $student->getKey(),
            'academic_year_id' => $academicYear->getKey(), 'term_id' => $term->getKey(),
            'total_score' => 80, 'average_score' => 80, 'position' => 1, 'status' => 'published', 'published_at' => now(),
        ]);

        return [$reportCard, $guardianUser];
    }

    public function test_result_is_withheld_when_policy_is_on_and_balance_is_owing(): void
    {
        [$reportCard] = $this->makeReportCardWithBalance(withholdPolicy: true, balance: 15000);

        $this->assertTrue(ResultAccessPolicy::isWithheldForDebt($reportCard));
    }

    public function test_result_is_not_withheld_when_policy_is_off_even_with_a_balance(): void
    {
        [$reportCard] = $this->makeReportCardWithBalance(withholdPolicy: false, balance: 15000);

        $this->assertFalse(ResultAccessPolicy::isWithheldForDebt($reportCard));
    }

    public function test_result_is_not_withheld_when_fully_paid(): void
    {
        [$reportCard] = $this->makeReportCardWithBalance(withholdPolicy: true, balance: 0);

        $this->assertFalse(ResultAccessPolicy::isWithheldForDebt($reportCard));
    }

    public function test_parent_gets_a_clear_403_when_downloading_a_withheld_result(): void
    {
        [$reportCard, $guardianUser] = $this->makeReportCardWithBalance(withholdPolicy: true, balance: 15000);

        $response = $this->actingAs($guardianUser)->get(route('report-cards.pdf', $reportCard));

        $response->assertForbidden();
        $response->assertSeeText('outstanding balance');
    }

    public function test_parent_can_download_once_the_balance_is_cleared(): void
    {
        [$reportCard, $guardianUser] = $this->makeReportCardWithBalance(withholdPolicy: true, balance: 0);

        $this->actingAs($guardianUser)
            ->get(route('report-cards.pdf', $reportCard))
            ->assertOk();
    }

    public function test_staff_can_still_view_a_withheld_result(): void
    {
        [$reportCard] = $this->makeReportCardWithBalance(withholdPolicy: true, balance: 15000);

        $school = $reportCard->school;

        $admin = User::query()->create([
            'name' => 'Fee Gate Admin', 'email' => 'fee-gate-admin@example.com',
            'password' => bcrypt('secret'), 'is_active' => true,
        ]);
        $admin->schools()->attach($school, ['role' => User::SCHOOL_ROLE_ADMIN, 'is_primary' => true]);

        $this->actingAs($admin)
            ->get(route('report-cards.pdf', $reportCard))
            ->assertOk();
    }
}
