<?php

namespace App\Http\Controllers;

use App\Models\SalaryPosting;
use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class SalaryMonthReportPdfController extends Controller
{
    public function __invoke(Request $request, School $school)
    {
        $user = $request->user();

        abort_unless(
            $user && (
                $user->isSuperAdmin()
                || $user->schools()->whereKey($school->getKey())->exists()
            ),
            403,
        );

        $month = Carbon::parse($request->query('month', now()->startOfMonth()))->startOfMonth();
        $postings = SalaryPosting::query()
            ->where('school_id', $school->getKey())
            ->whereDate('payroll_month', $month->toDateString())
            ->orderBy('staff_name')
            ->get();

        $logoDataUri = null;

        if ($school->logo_path && Storage::disk('public')->exists($school->logo_path)) {
            $path = Storage::disk('public')->path($school->logo_path);
            $mime = mime_content_type($path) ?: 'image/png';
            $logoDataUri = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
        }

        return Pdf::loadView('pdf.salary-month-report', [
            'school' => $school,
            'month' => $month,
            'postings' => $postings,
            'logoDataUri' => $logoDataUri,
            'totals' => [
                'basic_salary' => $postings->sum('basic_salary'),
                'allowances_total' => $postings->sum('allowances_total'),
                'gross_pay' => $postings->sum('gross_pay'),
                'deductions_total' => $postings->sum('deductions_total'),
                'net_pay' => $postings->sum('net_pay'),
            ],
        ])->setPaper('a4', 'landscape')->stream('salary-report-'.$month->format('Y-m').'.pdf');
    }
}
