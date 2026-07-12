<?php

namespace Tests\Feature;

use App\Mail\CommunicationLogMail;
use App\Models\CommunicationLog;
use App\Models\FeePayment;
use App\Models\Reminder;
use App\Models\StudentInvoice;
use App\Support\PaymentCommunicationCoordinator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentCommunicationCoordinatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_invoice_reminders_for_guardian_sms_and_email_contacts(): void
    {
        $this->seed();

        $invoice = StudentInvoice::query()
            ->where('invoice_number', 'INV-2026-0001')
            ->firstOrFail();

        $logs = app(PaymentCommunicationCoordinator::class)
            ->queueInvoiceReminder($invoice);

        $this->assertCount(2, $logs);
        $this->assertDatabaseHas(CommunicationLog::class, [
            'school_id' => $invoice->school_id,
            'student_id' => $invoice->student_id,
            'event_type' => 'fee_invoice_created',
            'channel' => 'sms',
            'recipient_contact' => '+2348011111111',
            'status' => 'queued',
        ]);
        $this->assertDatabaseHas(CommunicationLog::class, [
            'school_id' => $invoice->school_id,
            'student_id' => $invoice->student_id,
            'event_type' => 'fee_invoice_created',
            'channel' => 'email',
            'recipient_contact' => 'guardian@example.com',
            'status' => 'sent',
        ]);
    }

    public function test_it_schedules_due_reminders_for_primary_guardian_sms_and_email_contacts(): void
    {
        $this->seed();

        $invoice = StudentInvoice::query()
            ->where('invoice_number', 'INV-2026-0001')
            ->firstOrFail();

        $reminders = app(PaymentCommunicationCoordinator::class)
            ->scheduleInvoiceDueReminders($invoice);

        $this->assertCount(2, $reminders);
        $this->assertDatabaseHas(Reminder::class, [
            'school_id' => $invoice->school_id,
            'student_invoice_id' => $invoice->getKey(),
            'type' => 'fee_due',
            'channel' => 'sms',
            'recipient_contact' => '+2348011111111',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas(Reminder::class, [
            'school_id' => $invoice->school_id,
            'student_invoice_id' => $invoice->getKey(),
            'type' => 'fee_due',
            'channel' => 'email',
            'recipient_contact' => 'guardian@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_it_queues_payment_confirmation_messages_for_guardians(): void
    {
        $this->seed();

        $payment = FeePayment::query()
            ->where('receipt_number', 'RCT-2026-0001')
            ->firstOrFail();

        $logs = app(PaymentCommunicationCoordinator::class)
            ->queuePaymentConfirmation($payment);

        $this->assertCount(2, $logs);
        $this->assertDatabaseHas(CommunicationLog::class, [
            'school_id' => $payment->school_id,
            'student_id' => $payment->student_id,
            'event_type' => 'fee_payment_received',
            'channel' => 'sms',
            'recipient_contact' => '+2348011111111',
            'status' => 'queued',
        ]);
        $this->assertDatabaseHas(CommunicationLog::class, [
            'school_id' => $payment->school_id,
            'student_id' => $payment->student_id,
            'event_type' => 'fee_payment_received',
            'channel' => 'email',
            'recipient_contact' => 'guardian@example.com',
            'status' => 'sent',
        ]);
    }

    public function test_it_sends_invoice_email_communication_logs_immediately(): void
    {
        Mail::fake();
        $this->seed();

        $invoice = StudentInvoice::query()
            ->where('invoice_number', 'INV-2026-0001')
            ->firstOrFail();

        app(PaymentCommunicationCoordinator::class)
            ->queueInvoiceReminder($invoice);

        Mail::assertSent(CommunicationLogMail::class, 1);
        $this->assertDatabaseHas(CommunicationLog::class, [
            'school_id' => $invoice->school_id,
            'student_id' => $invoice->student_id,
            'channel' => 'email',
            'recipient_contact' => 'guardian@example.com',
            'status' => 'sent',
        ]);
    }
}
