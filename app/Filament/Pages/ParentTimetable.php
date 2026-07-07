<?php

namespace App\Filament\Pages;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\TimetableEntry;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ParentTimetable extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Timetable';

    protected static ?string $title = 'My Children\'s Timetable';

    protected static string|\UnitEnum|null $navigationGroup = 'Parent Portal';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.parent-timetable';

    public static function canAccess(): bool
    {
        return (bool) Filament::auth()->user()?->hasSchoolRole(Filament::getTenant(), 'parent');
    }

    protected function getViewData(): array
    {
        $tenant = Filament::getTenant();

        $children = Student::query()
            ->where('school_id', $tenant?->getKey())
            ->whereHas('guardianLinks.guardian', fn (Builder $query) => $query
                ->where('user_id', Filament::auth()->id()))
            ->get();

        $schedules = $children
            ->map(function (Student $student) use ($tenant): ?array {
                $enrollment = Enrollment::query()
                    ->with(['schoolClass', 'classSection'])
                    ->where('student_id', $student->getKey())
                    ->where('status', 'active')
                    ->orderByDesc('id')
                    ->first();

                if (! $enrollment) {
                    return null;
                }

                $entries = TimetableEntry::query()
                    ->with(['subject', 'staff'])
                    ->where('school_id', $tenant?->getKey())
                    ->where('school_class_id', $enrollment->school_class_id)
                    ->when(
                        $enrollment->class_section_id,
                        fn (Builder $query, $sectionId) => $query->where(fn (Builder $query) => $query
                            ->whereNull('class_section_id')
                            ->orWhere('class_section_id', $sectionId)),
                    )
                    ->orderBy('period_number')
                    ->get();

                return [
                    'student' => $student,
                    'classLabel' => collect([
                        $enrollment->schoolClass?->name,
                        $enrollment->classSection?->name,
                    ])->filter()->join(' '),
                    'entries' => $entries,
                ];
            })
            ->filter()
            ->values();

        return [
            'schedules' => $schedules,
        ];
    }
}
