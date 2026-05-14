<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ClassSubjects\ClassSubjectResource;
use App\Filament\Resources\ReportCards\ReportCardResource;
use App\Filament\Resources\StudentScores\StudentScoreResource;
use App\Models\Enrollment;
use App\Models\ReportCard;
use App\Models\StudentScore;
use App\Support\TeacherWorkspace;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class TeacherDashboard extends Widget
{
    protected string $view = 'filament.widgets.teacher-dashboard';

    protected static ?int $sort = -40;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return TeacherWorkspace::isTeacher();
    }

    protected function getViewData(): array
    {
        $staff = TeacherWorkspace::currentStaff();
        $tenant = Filament::getTenant();
        $formAssignments = TeacherWorkspace::formAssignments();
        $subjectAssignments = TeacherWorkspace::subjectAssignments();
        $formClassIds = $formAssignments->pluck('school_class_id')->filter()->unique()->values()->all();
        $formArmIds = $formAssignments->pluck('class_section_id')->filter()->unique()->values()->all();

        $classStudents = $formClassIds === []
            ? 0
            : Enrollment::query()
                ->where('school_id', $tenant?->getKey())
                ->whereIn('school_class_id', $formClassIds)
                ->when($formArmIds !== [], fn (Builder $query) => $query->whereIn('class_section_id', $formArmIds))
                ->where('status', 'active')
                ->distinct('student_id')
                ->count('student_id');

        $pendingReviews = $formClassIds === []
            ? 0
            : ReportCard::query()
                ->where('school_id', $tenant?->getKey())
                ->whereNull('teacher_comment')
                ->whereHas('student.enrollments', function (Builder $query) use ($tenant, $formClassIds, $formArmIds): void {
                    $query
                        ->where('school_id', $tenant?->getKey())
                        ->whereIn('school_class_id', $formClassIds)
                        ->when($formArmIds !== [], fn (Builder $query) => $query->whereIn('class_section_id', $formArmIds))
                        ->where('status', 'active');
                })
                ->count();

        $draftScores = $staff
            ? StudentScore::query()
                ->where('school_id', $tenant?->getKey())
                ->where('staff_id', $staff->getKey())
                ->where('status', 'draft')
                ->count()
            : 0;

        return [
            'staff' => $staff,
            'schoolName' => $tenant?->baseSchoolName() ?? 'School Dice',
            'cards' => [
                [
                    'label' => 'Form Classes',
                    'value' => $formAssignments->count(),
                    'description' => 'Classes or arms under your care.',
                    'icon' => 'heroicon-o-home-modern',
                ],
                [
                    'label' => 'Class Students',
                    'value' => $classStudents,
                    'description' => 'Active students in your form class.',
                    'icon' => 'heroicon-o-user-group',
                ],
                [
                    'label' => 'Teaching Load',
                    'value' => $subjectAssignments->count(),
                    'description' => 'Subject/class assignments.',
                    'icon' => 'heroicon-o-book-open',
                ],
                [
                    'label' => 'Draft Scores',
                    'value' => $draftScores,
                    'description' => 'Saved scores awaiting submission.',
                    'icon' => 'heroicon-o-pencil-square',
                ],
                [
                    'label' => 'Pending Reviews',
                    'value' => $pendingReviews,
                    'description' => 'Results needing class teacher remark.',
                    'icon' => 'heroicon-o-clipboard-document-check',
                ],
            ],
            'formAssignments' => $formAssignments->take(4),
            'subjectAssignments' => $subjectAssignments->take(5),
            'actions' => [
                [
                    'label' => 'Enter Scores',
                    'url' => StudentScoreResource::getUrl('index'),
                    'icon' => 'heroicon-o-pencil-square',
                ],
                [
                    'label' => 'Review Results',
                    'url' => ReportCardResource::getUrl('index'),
                    'icon' => 'heroicon-o-document-chart-bar',
                ],
                [
                    'label' => 'Class Subjects',
                    'url' => ClassSubjectResource::getUrl('index'),
                    'icon' => 'heroicon-o-clipboard-document-list',
                ],
            ],
        ];
    }
}
