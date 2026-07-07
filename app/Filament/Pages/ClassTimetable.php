<?php

namespace App\Filament\Pages;

use App\Models\ClassSection;
use App\Models\SchoolClass;
use App\Models\TimetableEntry;
use App\Support\TeacherWorkspace;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class ClassTimetable extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $navigationLabel = 'Timetable Preview';

    protected static ?string $title = 'Class Timetable';

    protected static string|\UnitEnum|null $navigationGroup = 'Classroom';

    protected static ?int $navigationSort = 25;

    protected string $view = 'filament.pages.class-timetable';

    public ?int $classId = null;

    public ?int $sectionId = null;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();

        if (! $user || ! $tenant) {
            return false;
        }

        if ($user->hasSchoolRole($tenant, 'parent')) {
            return false;
        }

        return true;
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return TeacherWorkspace::isTeacher() ? 'Teacher Workspace' : static::$navigationGroup;
    }

    public static function getNavigationSort(): ?int
    {
        return TeacherWorkspace::isTeacher() ? 40 : static::$navigationSort;
    }

    public function mount(): void
    {
        $this->classId ??= array_key_first($this->classOptions()) ?: null;
    }

    public function updatedClassId(): void
    {
        $this->sectionId = null;
    }

    protected function getViewData(): array
    {
        return [
            'classOptions' => $this->classOptions(),
            'sectionOptions' => $this->sectionOptions(),
            'entries' => $this->entries(),
            'classLabel' => $this->classLabel(),
        ];
    }

    /** @return array<int, string> */
    protected function classOptions(): array
    {
        $query = SchoolClass::query()
            ->where('school_id', Filament::getTenant()?->getKey())
            ->where('is_active', true)
            ->orderBy('name');

        if (TeacherWorkspace::isTeacher()) {
            $query->whereKey(TeacherWorkspace::formClassIds() ?: [0]);
        }

        return $query->pluck('name', 'id')->all();
    }

    /** @return array<int, string> */
    protected function sectionOptions(): array
    {
        if (! $this->classId) {
            return [];
        }

        return ClassSection::query()
            ->where('school_class_id', $this->classId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    protected function entries(): Collection
    {
        if (! $this->classId) {
            return collect();
        }

        return TimetableEntry::query()
            ->with(['subject', 'staff'])
            ->where('school_id', Filament::getTenant()?->getKey())
            ->where('school_class_id', $this->classId)
            ->when(
                $this->sectionId,
                fn ($query, $sectionId) => $query->where(fn ($query) => $query
                    ->whereNull('class_section_id')
                    ->orWhere('class_section_id', $sectionId)),
            )
            ->orderBy('period_number')
            ->get();
    }

    protected function classLabel(): string
    {
        return collect([
            SchoolClass::query()->find($this->classId)?->name,
            $this->sectionId ? ClassSection::query()->find($this->sectionId)?->name : null,
        ])->filter()->join(' ');
    }
}
