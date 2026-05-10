<?php

namespace App\Http\Controllers;

use App\Models\CompiledResult;
use App\Models\ReportCard;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportCardPdfController extends Controller
{
    public function __invoke(Request $request, ReportCard $reportCard)
    {
        $user = $request->user();

        abort_unless(
            $user && ($user->is_platform_admin || $user->schools()->whereKey($reportCard->school_id)->exists()),
            403,
        );

        $reportCard->load([
            'school',
            'student.enrollments.schoolClass',
            'student.enrollments.classSection',
            'exam',
            'academicYear',
            'term',
        ]);

        $results = CompiledResult::query()
            ->with('subject')
            ->where('exam_id', $reportCard->exam_id)
            ->where('student_id', $reportCard->student_id)
            ->orderBy('subject_id')
            ->get();

        $placement = $reportCard->student
            ?->enrollments
            ->where('academic_year_id', $reportCard->academic_year_id)
            ->when($reportCard->term_id, fn ($enrollments) => $enrollments->where('term_id', $reportCard->term_id))
            ->sortByDesc('enrolled_on')
            ->first()
            ?? $reportCard->student?->enrollments->sortByDesc('enrolled_on')->first();

        $logoDataUri = null;

        if ($reportCard->school?->logo_path && Storage::disk('public')->exists($reportCard->school->logo_path)) {
            $path = Storage::disk('public')->path($reportCard->school->logo_path);
            $mime = mime_content_type($path) ?: 'image/png';
            $logoDataUri = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
        }

        $filename = 'report-card-'.Str::slug((string) ($reportCard->student?->admission_number ?? $reportCard->getKey())).'.pdf';

        return Pdf::loadView('pdf.report-card', [
            'reportCard' => $reportCard,
            'school' => $reportCard->school,
            'student' => $reportCard->student,
            'placement' => $placement,
            'results' => $results,
            'logoDataUri' => $logoDataUri,
        ])->setPaper('a4')->stream($filename);
    }
}
