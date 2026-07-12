<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function otherSchoolWithData(): School
    {
        $other = School::query()->create([
            'name' => 'Rival Academy',
            'code' => 'RIVAL-001',
            'slug' => 'rival-academy',
            'email' => 'rival@example.com',
            'country' => 'Nigeria',
            'is_active' => true,
        ]);

        SchoolClass::query()->create([
            'school_id' => $other->getKey(),
            'name' => 'Zebra Leak Class',
            'code' => 'ZLC-1',
            'level' => 1,
            'is_active' => true,
        ]);

        Student::query()->create([
            'school_id' => $other->getKey(),
            'admission_number' => 'RIVAL-ADM-001',
            'first_name' => 'Zebulon',
            'last_name' => 'Leakwatch',
            'gender' => 'male',
            'status' => 'active',
        ]);

        return $other;
    }

    public function test_school_portal_never_shows_another_schools_records_or_options(): void
    {
        $this->seed();
        $this->otherSchoolWithData();

        $schoolAdmin = User::query()->where('email', 'principal@demo-school.test')->firstOrFail();
        $slug = $schoolAdmin->schools()->value('slug');

        // Table listings must not leak the rival school's rows.
        $this
            ->actingAs($schoolAdmin)
            ->get("/portal/{$slug}/students")
            ->assertOk()
            ->assertDontSeeText('Zebulon');

        $this
            ->actingAs($schoolAdmin)
            ->get("/portal/{$slug}/school-classes")
            ->assertOk()
            ->assertDontSeeText('Zebra Leak Class');

        // Form dropdown options (raw Model::query() closures) must not leak either.
        $this
            ->actingAs($schoolAdmin)
            ->get("/portal/{$slug}/assignments/create")
            ->assertOk()
            ->assertDontSeeText('Zebra Leak Class');

        $this
            ->actingAs($schoolAdmin)
            ->get("/portal/{$slug}/student-devices/create")
            ->assertOk()
            ->assertDontSeeText('Zebulon');
    }

    public function test_document_numbers_are_sequential_per_school(): void
    {
        $this->seed();
        $other = $this->otherSchoolWithData();

        $demoStudent = Student::query()
            ->whereNot('school_id', $other->getKey())
            ->firstOrFail();

        $rivalStudent = Student::query()
            ->where('school_id', $other->getKey())
            ->firstOrFail();

        $demoYear = AcademicYear::query()
            ->where('school_id', $demoStudent->school_id)
            ->firstOrFail();

        $rivalYear = AcademicYear::query()->create([
            'school_id' => $other->getKey(),
            'name' => '2026/2027',
            'starts_on' => '2026-09-01',
            'ends_on' => '2027-07-31',
            'is_current' => true,
            'is_active' => true,
        ]);

        $demoInvoice = StudentInvoice::query()->create([
            'school_id' => $demoStudent->school_id,
            'student_id' => $demoStudent->getKey(),
            'academic_year_id' => $demoYear->getKey(),
            'invoice_date' => today(),
            'subtotal' => 1000,
            'total' => 1000,
            'balance' => 1000,
            'status' => 'unpaid',
        ]);

        $rivalFirstInvoice = StudentInvoice::query()->create([
            'school_id' => $other->getKey(),
            'student_id' => $rivalStudent->getKey(),
            'academic_year_id' => $rivalYear->getKey(),
            'invoice_date' => today(),
            'subtotal' => 5000,
            'total' => 5000,
            'balance' => 5000,
            'status' => 'unpaid',
        ]);

        // A brand-new school starts from 0001 regardless of other schools' volume.
        $this->assertStringEndsWith('-0001', $rivalFirstInvoice->invoice_number);
        $this->assertNotSame($demoInvoice->invoice_number, $rivalFirstInvoice->invoice_number);
    }
}
