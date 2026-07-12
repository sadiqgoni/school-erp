<?php

namespace Tests\Feature;

use App\Models\FeePayment;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentDevice;
use App\Models\StudentInvoice;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_student_does_not_destroy_their_invoices_and_payments(): void
    {
        $this->seed();

        $invoice = StudentInvoice::query()->firstOrFail();
        $payment = FeePayment::query()->where('student_invoice_id', $invoice->getKey())->first();
        $student = $invoice->student;

        $student->delete();

        $this->assertSoftDeleted($student);
        $this->assertDatabaseHas('student_invoices', ['id' => $invoice->getKey()]);

        if ($payment) {
            $this->assertDatabaseHas('fee_payments', ['id' => $payment->getKey()]);
        }

        // Hidden from normal queries…
        $this->assertNull(Student::query()->find($student->getKey()));

        // …but recoverable.
        $this->assertNotNull(Student::withTrashed()->find($student->getKey()));
    }

    public function test_deleted_school_disappears_from_superadmin_tenant_list_but_is_recoverable(): void
    {
        $this->seed();

        $superadmin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $school = School::query()->create([
            'name' => 'Closing Down Academy',
            'code' => 'CLOSE-001',
            'slug' => 'closing-down-academy',
            'division' => School::DIVISION_SECONDARY,
            'email' => 'closing@example.com',
            'country' => 'Nigeria',
            'is_active' => true,
        ]);

        $school->delete();

        $tenants = $superadmin->getTenants(app(Panel::class)::make()->id('school'));

        $this->assertFalse(
            collect($tenants)->contains(fn (School $tenant): bool => $tenant->getKey() === $school->getKey()),
        );

        $this->assertNotNull(School::withTrashed()->find($school->getKey()));
    }

    public function test_deleting_a_school_hides_its_recoverable_records_until_restore(): void
    {
        $this->seed();

        $invoice = StudentInvoice::query()->firstOrFail();
        $school = School::query()
            ->withoutGlobalScope('school-panel-current-tenant')
            ->findOrFail($invoice->school_id);
        $student = $invoice->student;
        $payment = FeePayment::query()->where('student_invoice_id', $invoice->getKey())->first();

        $school->delete();

        $this->assertSoftDeleted($school);
        $this->assertSoftDeleted($student);
        $this->assertSoftDeleted($invoice);

        if ($payment) {
            $this->assertSoftDeleted($payment);
        }

        $school->restore();

        $this->assertNull($school->fresh()->deleted_at);
        $this->assertNull(Student::withTrashed()->find($student->getKey())->deleted_at);
        $this->assertNull(StudentInvoice::withTrashed()->find($invoice->getKey())->deleted_at);

        if ($payment) {
            $this->assertNull(FeePayment::withTrashed()->find($payment->getKey())->deleted_at);
        }
    }

    public function test_permanently_deleting_a_school_removes_its_records(): void
    {
        $this->seed();

        $invoice = StudentInvoice::query()->firstOrFail();
        $school = School::query()
            ->withoutGlobalScope('school-panel-current-tenant')
            ->findOrFail($invoice->school_id);
        $student = $invoice->student;
        $payment = FeePayment::query()->where('student_invoice_id', $invoice->getKey())->first();

        $school->forceDelete();

        $this->assertDatabaseMissing('schools', ['id' => $school->getKey()]);
        $this->assertDatabaseMissing('students', ['id' => $student->getKey()]);
        $this->assertDatabaseMissing('student_invoices', ['id' => $invoice->getKey()]);

        if ($payment) {
            $this->assertDatabaseMissing('fee_payments', ['id' => $payment->getKey()]);
        }
    }

    public function test_deleted_school_device_token_no_longer_authenticates(): void
    {
        $this->seed();

        $student = Student::query()->firstOrFail();
        $school = School::query()->withoutGlobalScope('school-panel-current-tenant')->findOrFail($student->school_id);
        $school->forceFill(['device_api_token' => 'soft-delete-test-token'])->save();

        StudentDevice::query()->create([
            'school_id' => $school->getKey(),
            'student_id' => $student->getKey(),
            'identifier' => 'CARD-SOFT-DELETE-TEST',
            'device_type' => 'nfc_card',
            'is_active' => true,
        ]);

        $school->delete();

        $this
            ->postJson('/devices/events', [
                'token' => 'soft-delete-test-token',
                'device' => 'CARD-SOFT-DELETE-TEST',
            ])
            ->assertUnauthorized();
    }

    public function test_admin_schools_page_hides_deleted_schools_by_default_and_they_remain_restorable(): void
    {
        $this->seed();

        $superadmin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $school = School::query()->create([
            'name' => 'Mistakenly Deleted Academy',
            'code' => 'MISTAKE-001',
            'slug' => 'mistakenly-deleted-academy',
            'division' => School::DIVISION_SECONDARY,
            'email' => 'mistake@example.com',
            'country' => 'Nigeria',
            'is_active' => true,
        ]);
        $school->delete();

        $this
            ->actingAs($superadmin)
            ->get('/admin/schools')
            ->assertOk()
            ->assertDontSeeText('Mistakenly Deleted Academy');

        // The page's TrashedFilter/RestoreAction ultimately operate on the
        // same query — confirm a trashed school is findable and restorable.
        $this->assertTrue(School::onlyTrashed()->whereKey($school->getKey())->exists());

        $school->restore();
        $this->assertNull($school->fresh()->deleted_at);
    }
}
