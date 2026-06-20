<?php

namespace App\Http\Controllers;

use App\Models\SalaryPosting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SalaryPostingPdfController extends Controller
{
    public function __invoke(Request $request, SalaryPosting $posting)
    {
        $user = $request->user();

        abort_unless(
            $user && (
                $user->isSuperAdmin()
                || $user->schools()->whereKey($posting->school_id)->exists()
            ),
            403,
        );

        $posting->load(['school', 'staff.department', 'staff.staffBank', 'salaryTemplate']);

        $logoDataUri = null;

        if ($posting->school?->logo_path && Storage::disk('public')->exists($posting->school->logo_path)) {
            $path = Storage::disk('public')->path($posting->school->logo_path);
            $mime = mime_content_type($path) ?: 'image/png';
            $logoDataUri = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
        }

        return Pdf::loadView('pdf.salary-posting', [
            'posting' => $posting,
            'school' => $posting->school,
            'logoDataUri' => $logoDataUri,
        ])->setPaper('a4')->stream($posting->reference.'-payslip.pdf');
    }
}
