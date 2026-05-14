<?php

namespace App\Support;

use App\Models\Staff;
use App\Models\TeachingAssignment;
use Filament\Facades\Filament;
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
}
