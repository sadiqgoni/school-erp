<?php

namespace App\Support;

use App\Models\CommunicationLog;
use App\Models\FeePayment;
use App\Models\GuardianStudent;
use App\Models\Reminder;
use App\Models\StudentInvoice;
use Illuminate\Database\Eloquent\Collection;

class PaymentCommunicationCoordinator
{
    /**
     * @return Collection<int, CommunicationLog>
     */
    public function queueInvoiceReminder(StudentInvoice $invoice, string $eventType = 'fee_invoice_created'): Collection
    {
        $invoice->loadMissing(['student.guardianLinks.guardian', 'school']);

        $logs = new Collection();

        foreach ($this->guardianLinksFor($invoice) as $link) {
            $guardian = $link->guardian;

            if (! $guardian) {
                continue;
            }

            $message = $this->invoiceReminderMessage($invoice);

            if ($link->receives_sms && filled($guardian->phone)) {
                $logs->push($this->createInvoiceCommunicationLog($invoice, $link, 'sms', $guardian->phone, $message, $eventType));
            }

            if (filled($guardian->email)) {
                $logs->push($this->createInvoiceCommunicationLog($invoice, $link, 'email', $guardian->email, $message, $eventType));
            }
        }

        return $logs;
    }

    /**
     * @return Collection<int, CommunicationLog>
     */
    public function queuePaymentConfirmation(FeePayment $payment): Collection
    {
        $payment->loadMissing(['studentInvoice.school', 'student.guardianLinks.guardian']);

        $logs = new Collection();
        $invoice = $payment->studentInvoice;

        if (! $invoice) {
            return $logs;
        }

        foreach ($this->guardianLinksFor($invoice) as $link) {
            $guardian = $link->guardian;

            if (! $guardian) {
                continue;
            }

            $message = $this->paymentConfirmationMessage($payment);

            if ($link->receives_sms && filled($guardian->phone)) {
                $logs->push(CommunicationLog::query()->create([
                    'school_id' => $payment->school_id,
                    'student_id' => $payment->student_id,
                    'guardian_id' => $guardian->getKey(),
                    'related_type' => FeePayment::class,
                    'related_id' => $payment->getKey(),
                    'event_type' => 'fee_payment_received',
                    'channel' => 'sms',
                    'recipient_name' => $guardian->name,
                    'recipient_contact' => $guardian->phone,
                    'subject' => 'Payment received',
                    'body' => $message,
                    'metadata' => [
                        'invoice_id' => $invoice->getKey(),
                        'receipt_number' => $payment->receipt_number,
                    ],
                ]));
            }

            if (filled($guardian->email)) {
                $logs->push(CommunicationLog::query()->create([
                    'school_id' => $payment->school_id,
                    'student_id' => $payment->student_id,
                    'guardian_id' => $guardian->getKey(),
                    'related_type' => FeePayment::class,
                    'related_id' => $payment->getKey(),
                    'event_type' => 'fee_payment_received',
                    'channel' => 'email',
                    'recipient_name' => $guardian->name,
                    'recipient_contact' => $guardian->email,
                    'subject' => 'Payment received',
                    'body' => $message,
                    'metadata' => [
                        'invoice_id' => $invoice->getKey(),
                        'receipt_number' => $payment->receipt_number,
                    ],
                ]));
            }
        }

        return $logs;
    }

    /**
     * @return Collection<int, Reminder>
     */
    public function scheduleInvoiceDueReminders(StudentInvoice $invoice): Collection
    {
        $invoice->loadMissing(['student.guardianLinks.guardian']);

        $reminders = new Collection();
        $scheduledFor = $invoice->due_date?->copy()->subDays(3)->startOfDay() ?? now();

        foreach ($this->guardianLinksFor($invoice) as $link) {
            $guardian = $link->guardian;

            if (! $guardian || ! $link->receives_sms || blank($guardian->phone)) {
                continue;
            }

            $reminders->push(Reminder::query()->create([
                'school_id' => $invoice->school_id,
                'student_invoice_id' => $invoice->getKey(),
                'student_id' => $invoice->student_id,
                'guardian_id' => $guardian->getKey(),
                'type' => 'fee_due',
                'channel' => 'sms',
                'recipient_contact' => $guardian->phone,
                'message' => $this->invoiceReminderMessage($invoice),
                'scheduled_for' => $scheduledFor,
                'metadata' => [
                    'invoice_number' => $invoice->invoice_number,
                    'balance' => $invoice->balance,
                ],
            ]));
        }

        return $reminders;
    }

    /**
     * @return Collection<int, GuardianStudent>
     */
    protected function guardianLinksFor(StudentInvoice $invoice): Collection
    {
        return $invoice->student?->guardianLinks
            ->filter(fn (GuardianStudent $link): bool => (bool) $link->guardian?->is_active)
            ->sortByDesc('is_primary_contact')
            ->values() ?? new Collection();
    }

    protected function createInvoiceCommunicationLog(
        StudentInvoice $invoice,
        GuardianStudent $link,
        string $channel,
        string $contact,
        string $message,
        string $eventType,
    ): CommunicationLog {
        return CommunicationLog::query()->create([
            'school_id' => $invoice->school_id,
            'student_id' => $invoice->student_id,
            'guardian_id' => $link->guardian_id,
            'related_type' => StudentInvoice::class,
            'related_id' => $invoice->getKey(),
            'event_type' => $eventType,
            'channel' => $channel,
            'recipient_name' => $link->guardian?->name,
            'recipient_contact' => $contact,
            'subject' => 'School fee invoice',
            'body' => $message,
            'metadata' => [
                'invoice_number' => $invoice->invoice_number,
                'payment_url' => $invoice->payment_url,
                'balance' => $invoice->balance,
            ],
        ]);
    }

    protected function invoiceReminderMessage(StudentInvoice $invoice): string
    {
        $studentName = $invoice->student?->full_name ?? 'your child';
        $schoolName = $invoice->school?->name ?? 'the school';
        $dueDate = $invoice->due_date?->format('d M Y') ?? 'soon';
        $amount = number_format((float) $invoice->balance, 2);
        $paymentText = filled($invoice->payment_url) ? " Pay online: {$invoice->payment_url}" : '';

        return "{$schoolName}: Fee invoice {$invoice->invoice_number} for {$studentName} has a balance of NGN {$amount}, due {$dueDate}.{$paymentText}";
    }

    protected function paymentConfirmationMessage(FeePayment $payment): string
    {
        $studentName = $payment->student?->full_name ?? 'your child';
        $schoolName = $payment->studentInvoice?->school?->name ?? 'the school';
        $amount = number_format((float) $payment->amount, 2);

        return "{$schoolName}: We received NGN {$amount} for {$studentName}. Receipt: {$payment->receipt_number}. Thank you.";
    }
}
