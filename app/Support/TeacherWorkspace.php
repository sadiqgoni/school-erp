<?php

namespace App\Support;

use App\Models\ClassSubject;
use App\Models\Staff;
use App\Models\TeachingAssignment;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TeacherWorkspace
{
    public static function isTeacher(): bool
    {
        return (bool) Filament::auth()->user()?->hasSchoolRole(Filament::getTenant(), 'teacher');
    }

    public static function currentStaff(): ?Staff
    {
        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();

        if (! $user || ! $tenant) {
            return null;
        }

        return Staff::query()
            ->where('school_id', $tenant->getKey())
            ->where('user_id', $user->getKey())
            ->first();
    }

    /**
     * @return array<int, int>
     */
    public static function formClassIds(): array
    {
        $staff = self::currentStaff();

        if (! $staff) {
            return [];
        }

        return TeachingAssignment::query()
            ->where('school_id', $staff->school_id)
            ->where('staff_id', $staff->getKey())
            ->whereIn('assignment_role', [
                TeachingAssignment::ROLE_FORM_TEACHER,
                TeachingAssignment::ROLE_ASSISTANT_FORM_TEACHER,
            ])
            ->where('is_active', true)
            ->pluck('school_class_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function formAssignments(): Collection
    {
        $staff = self::currentStaff();

        if (! $staff) {
            return collect();
        }

        return TeachingAssignment::query()
            ->with(['academicYear', 'term', 'schoolClass', 'classSection'])
            ->where('school_id', $staff->school_id)
            ->where('staff_id', $staff->getKey())
            ->whereIn('assignment_role', [
                TeachingAssignment::ROLE_FORM_TEACHER,
                TeachingAssignment::ROLE_ASSISTANT_FORM_TEACHER,
            ])
            ->where('is_active', true)
            ->latest()
            ->get();
    }

    public static function singleFormAssignment(): ?TeachingAssignment
    {
        $assignments = self::formAssignments()
            ->unique(fn (TeachingAssignment $assignment): string => implode(':', [
                $assignment->school_class_id,
                $assignment->class_section_id ?: 'all',
            ]))
            ->values();

        if ($assignments->count() !== 1) {
            return null;
        }

        return $assignments->first();
    }

    public static function shouldLockToFormAssignment(): bool
    {
        return self::isTeacher() && self::singleFormAssignment() !== null;
    }

    public static function lockedFormClassId(): ?int
    {
        return self::singleFormAssignment()?->school_class_id;
    }

    public static function lockedFormSectionId(): ?int
    {
        return self::singleFormAssignment()?->class_section_id;
    }

    /**
     * Class ids the teacher works with: form classes plus classes where they teach a subject.
     *
     * @return array<int, int>
     */
    public static function teachableClassIds(): array
    {
        $staff = self::currentStaff();

        if (! $staff) {
            return [];
        }

        $assignmentClassIds = TeachingAssignment::query()
            ->where('school_id', $staff->school_id)
            ->where('staff_id', $staff->getKey())
            ->where('is_active', true)
            ->pluck('school_class_id');

        $subjectClassIds = ClassSubject::query()
            ->where('school_id', $staff->school_id)
            ->where('staff_id', $staff->getKey())
            ->where('is_active', true)
            ->pluck('school_class_id');

        return $assignmentClassIds
            ->merge($subjectClassIds)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Arm/section ids the teacher may act on within one specific class.
     * Returns null when there is no arm-level restriction to apply (e.g.
     * they only reach this class as a subject teacher, who works across
     * every arm, or their form assignment covers the whole class).
     *
     * @return array<int, int>|null
     */
    public static function teachableSectionIds(int $classId): ?array
    {
        $staff = self::currentStaff();

        if (! $staff) {
            return [];
        }

        $formSections = TeachingAssignment::query()
            ->where('school_id', $staff->school_id)
            ->where('staff_id', $staff->getKey())
            ->where('school_class_id', $classId)
            ->whereIn('assignment_role', [
                TeachingAssignment::ROLE_FORM_TEACHER,
                TeachingAssignment::ROLE_ASSISTANT_FORM_TEACHER,
            ])
            ->where('is_active', true)
            ->pluck('class_section_id');

        if ($formSections->isEmpty() || $formSections->contains(null)) {
            return null;
        }

        return $formSections->unique()->values()->all();
    }

    public static function subjectAssignments(): Collection
    {
        $staff = self::currentStaff();

        if (! $staff) {
            return collect();
        }

        return TeachingAssignment::query()
            ->with(['academicYear', 'term', 'schoolClass', 'classSection', 'subject'])
            ->where('school_id', $staff->school_id)
            ->where('staff_id', $staff->getKey())
            ->where('assignment_role', TeachingAssignment::ROLE_SUBJECT_TEACHER)
            ->where('is_active', true)
            ->orderBy('school_class_id')
            ->get();
    }

    public static function applyFormAssignmentScope(
        Builder $query,
        string $classColumn = 'school_class_id',
        string $sectionColumn = 'class_section_id',
    ): Builder {
        $assignments = self::formAssignments();

        if ($assignments->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($assignments, $classColumn, $sectionColumn): void {
            $assignments->each(function (TeachingAssignment $assignment) use ($query, $classColumn, $sectionColumn): void {
                $query->orWhere(function (Builder $query) use ($assignment, $classColumn, $sectionColumn): void {
                    $query->where($classColumn, $assignment->school_class_id);

                    if ($assignment->class_section_id) {
                        $query->where(function (Builder $query) use ($assignment, $sectionColumn): void {
                            $query
                                ->whereNull($sectionColumn)
                                ->orWhere($sectionColumn, $assignment->class_section_id);
                        });

                        return;
                    }

                    $query->whereNull($sectionColumn);
                });
            });
        });
    }
}
