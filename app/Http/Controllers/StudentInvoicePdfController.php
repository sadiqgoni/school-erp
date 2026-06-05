<?php

namespace App\Http\Controllers;

use App\Models\StudentInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentInvoicePdfController extends Controller
{
    public function __invoke(Request $request, StudentInvoice $invoice)
    {
        $user = $request->user();

        abort_unless(
            $user && (
                $user->is_platform_admin
                || (
                    $user->schools()->whereKey($invoice->school_id)->exists()
                    && (
                        ! $user->hasSchoolRole($invoice->school_id, 'parent')
                        || $user->guardians()
                            ->where('school_id', $invoice->school_id)
                            ->whereHas('studentLinks', fn ($query) => $query->where('student_id', $invoice->student_id))
                            ->exists()
                    )
                )
            ),
            403,
        );

        $invoice->load([
            'school',
            'student.enrollments.academicYear',
            'student.enrollments.term',
            'student.enrollments.schoolClass',
            'student.enrollments.classSection',
            'academicYear',
            'term',
            'items.feeType',
            'payments.receivedBy',
        ]);

        $enrollments = $invoice->student?->enrollments ?? collect();

        $placement = $enrollments
            ->where('academic_year_id', $invoice->academic_year_id)
            ->when($invoice->term_id, fn ($enrollments) => $enrollments->where('term_id', $invoice->term_id))
            ->sortByDesc('enrolled_on')
            ->first()
            ?? $enrollments->sortByDesc('enrolled_on')->first();

        $logoDataUri = null;

        if ($invoice->school?->logo_path && Storage::disk('public')->exists($invoice->school->logo_path)) {
            $path = Storage::disk('public')->path($invoice->school->logo_path);
            $mime = mime_content_type($path) ?: 'image/png';
            $logoDataUri = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
        }

        return Pdf::loadView('pdf.student-invoice', [
            'invoice' => $invoice,
            'school' => $invoice->school,
            'student' => $invoice->student,
            'placement' => $placement,
            'logoDataUri' => $logoDataUri,
            'amountInWords' => $this->amountInWords((float) $invoice->total),
        ])->setPaper('a4')->stream($invoice->invoice_number.'.pdf');
    }

    protected function amountInWords(float $amount): string
    {
        $naira = (int) floor($amount);
        $kobo = (int) round(($amount - $naira) * 100);

        $words = $this->numberToWords($naira).' Naira';

        if ($kobo > 0) {
            $words .= ' '.$this->numberToWords($kobo).' Kobo';
        }

        return str($words)->title()->toString();
    }

    protected function numberToWords(int $number): string
    {
        if ($number === 0) {
            return 'zero';
        }

        $units = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
        $scales = [
            1000000000 => 'billion',
            1000000 => 'million',
            1000 => 'thousand',
            100 => 'hundred',
        ];

        foreach ($scales as $value => $label) {
            if ($number >= $value) {
                $major = intdiv($number, $value);
                $remainder = $number % $value;

                return trim($this->numberToWords($major).' '.$label.' '.($remainder ? $this->numberToWords($remainder) : ''));
            }
        }

        if ($number >= 20) {
            $remainder = $number % 10;

            return trim($tens[intdiv($number, 10)].' '.($remainder ? $units[$remainder] : ''));
        }

        return $units[$number];
    }
}
