<?php

namespace App\Support;

use App\Models\FeePayment;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentInvoice;

class WhatsApp
{
    /**
     * Normalize a Nigerian phone number into international digits for wa.me.
     * Accepts 0803…, +234803…, 234803…, or bare 803… formats.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (blank($digits)) {
            return null;
        }

        if (str_starts_with($digits, '234')) {
            return $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '234'.substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            return '234'.$digits;
        }

        return $digits;
    }

    public static function link(?string $phone, string $message): ?string
    {
        $normalized = self::normalizePhone($phone);

        if (! $normalized) {
            return null;
        }

        return 'https://wa.me/'.$normalized.'?text='.rawurlencode($message);
    }

    /**
     * The best guardian to contact for a student (primary contact first).
     * Memoized per student so table rows don't repeat queries.
     */
    public static function guardianContact(Student $student): ?Guardian
    {
        static $cache = [];

        if (array_key_exists($student->getKey(), $cache)) {
            return $cache[$student->getKey()];
        }

        return $cache[$student->getKey()] = $student->guardianLinks()
            ->with('guardian')
            ->orderByDesc('is_primary_contact')
            ->get()
            ->pluck('guardian')
            ->filter(fn (?Guardian $guardian): bool => filled($guardian?->phone))
            ->first();
    }

    public static function invoiceReminderLink(StudentInvoice $invoice): ?string
    {
        $student = $invoice->student;

        if (! $student) {
            return null;
        }

        $guardian = self::guardianContact($student);

        if (! $guardian) {
            return null;
        }

        $school = $invoice->school;

        $message = sprintf(
            "Hello %s, this is %s.\n\nSchool fees invoice %s for %s:\nTotal: ₦%s\nPaid: ₦%s\nOutstanding: ₦%s%s\n\nYou can view the invoice and pay securely on the parent portal:\n%s\n\nThank you.",
            $guardian->name,
            $school?->name ?? 'the school',
            $invoice->invoice_number,
            $student->full_name,
            number_format((float) $invoice->total, 2),
            number_format((float) $invoice->amount_paid, 2),
            number_format((float) $invoice->balance, 2),
            $invoice->due_date ? "\nDue date: ".$invoice->due_date->format('d M Y') : '',
            $school?->portalUrl() ?? url('/'),
        );

        return self::link($guardian->phone, $message);
    }

    public static function receiptLink(FeePayment $payment): ?string
    {
        $student = $payment->student;

        if (! $student) {
            return null;
        }

        $guardian = self::guardianContact($student);

        if (! $guardian) {
            return null;
        }

        $school = $payment->school;
        $balance = $payment->studentInvoice?->balance;

        $message = sprintf(
            "Hello %s, this is %s.\n\nWe have received your payment of ₦%s for %s.\nReceipt number: %s%s\n\nThank you for paying on time!",
            $guardian->name,
            $school?->name ?? 'the school',
            number_format((float) $payment->amount, 2),
            $student->full_name,
            $payment->receipt_number,
            $balance !== null ? "\nRemaining balance: ₦".number_format((float) $balance, 2) : '',
        );

        return self::link($guardian->phone, $message);
    }
}
