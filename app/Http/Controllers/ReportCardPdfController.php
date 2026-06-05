<?php

namespace App\Http\Controllers;

use App\Models\CompiledResult;
use App\Models\ReportCard;
use App\Models\StudentScore;
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
            $user && (
                $user->is_platform_admin
                || (
                    $user->schools()->whereKey($reportCard->school_id)->exists()
                    && (
                        ! $user->hasSchoolRole($reportCard->school_id, 'parent')
                        || $user->guardians()
                            ->where('school_id', $reportCard->school_id)
                            ->whereHas('studentLinks', fn ($query) => $query->where('student_id', $reportCard->student_id))
                            ->exists()
                    )
                )
            ),
            403,
        );

        $reportCard->load([
            'school',
            'student.enrollments.schoolClass',
            'student.enrollments.classSection',
            'exam',
            'exam.components',
            'academicYear',
            'term',
            'traitRatings.traitItem',
        ]);

        $results = CompiledResult::query()
            ->with('subject')
            ->where('exam_id', $reportCard->exam_id)
            ->where('student_id', $reportCard->student_id)
            ->orderBy('subject_id')
            ->get();

        $components = $reportCard->exam
            ?->components
            ->where('is_active', true)
            ->sortBy('position')
            ->values() ?? collect();

        $scoreMatrix = StudentScore::query()
            ->where('exam_id', $reportCard->exam_id)
            ->where('student_id', $reportCard->student_id)
            ->whereIn('status', ['submitted', 'approved'])
            ->get()
            ->groupBy('subject_id')
            ->map(fn ($scores) => $scores->keyBy('assessment_component_id'));

        $placement = $reportCard->student
            ?->enrollments
            ->where('academic_year_id', $reportCard->academic_year_id)
            ->when($reportCard->term_id, fn ($enrollments) => $enrollments->where('term_id', $reportCard->term_id))
            ->sortByDesc('enrolled_on')
            ->first()
            ?? $reportCard->student?->enrollments->sortByDesc('enrolled_on')->first();

        $highestClassAverage = ReportCard::query()
            ->where('exam_id', $reportCard->exam_id)
            ->whereHas('student.enrollments', function ($query) use ($placement, $reportCard): void {
                $query
                    ->where('academic_year_id', $reportCard->academic_year_id)
                    ->when($reportCard->term_id, fn ($query, $termId) => $query->where('term_id', $termId))
                    ->when($placement?->school_class_id, fn ($query, $classId) => $query->where('school_class_id', $classId))
                    ->when($placement?->class_section_id, fn ($query, $sectionId) => $query->where('class_section_id', $sectionId))
                    ->where('status', 'active');
            })
            ->max('average_score');

        $logoDataUri = null;

        if ($reportCard->school?->logo_path && Storage::disk('public')->exists($reportCard->school->logo_path)) {
            $path = Storage::disk('public')->path($reportCard->school->logo_path);
            $mime = mime_content_type($path) ?: 'image/png';
            $logoDataUri = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
        }

        $studentPhotoDataUri = null;

        if ($reportCard->student?->photo_path && Storage::disk('public')->exists($reportCard->student->photo_path)) {
            $path = Storage::disk('public')->path($reportCard->student->photo_path);
            $mime = mime_content_type($path) ?: 'image/png';
            $studentPhotoDataUri = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
        }

        $filename = 'report-card-'.Str::slug((string) ($reportCard->student?->admission_number ?? $reportCard->getKey())).'.pdf';

        return Pdf::loadView('pdf.report-card', [
            'reportCard' => $reportCard,
            'school' => $reportCard->school,
            'student' => $reportCard->student,
            'placement' => $placement,
            'results' => $results,
            'components' => $components,
            'totalMaxScore' => $components->sum(fn ($component): float => (float) $component->max_score),
            'expectedTotalScore' => $components->sum(fn ($component): float => (float) $component->max_score) * $results->count(),
            'highestClassAverage' => $highestClassAverage,
            'cgpa' => min(5, round(((float) $reportCard->average_score / 100) * 5, 2)),
            'scoreMatrix' => $scoreMatrix,
            'affectiveRatings' => $reportCard->traitRatings
                ->filter(fn ($rating): bool => $rating->traitItem?->category === 'affective')
                ->sortBy(fn ($rating) => $rating->traitItem?->position ?? 0)
                ->values(),
            'psychomotorRatings' => $reportCard->traitRatings
                ->filter(fn ($rating): bool => $rating->traitItem?->category === 'psychomotor')
                ->sortBy(fn ($rating) => $rating->traitItem?->position ?? 0)
                ->values(),
            'logoDataUri' => $logoDataUri,
            'studentPhotoDataUri' => $studentPhotoDataUri,
        ])->setPaper('a4')->stream($filename);
    }
}
