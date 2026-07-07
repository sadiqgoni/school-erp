<?php

namespace App\Filament\Pages;

use App\Models\Enrollment;
use App\Models\Notice;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ParentNotices extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'School Notices';

    protected static ?string $title = 'School Notices';

    protected static string|\UnitEnum|null $navigationGroup = 'Parent Portal';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.parent-notices';

    public static function canAccess(): bool
    {
        return (bool) Filament::auth()->user()?->hasSchoolRole(Filament::getTenant(), 'parent');
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canAccess()) {
            return null;
        }

        $count = static::noticesForParent()->count();

        return $count > 0 ? (string) $count : null;
    }

    protected function getViewData(): array
    {
        return [
            'notices' => static::noticesForParent(),
        ];
    }

    protected static function noticesForParent(): Collection
    {
        $tenant = Filament::getTenant();

        $placements = Enrollment::query()
            ->with('schoolClass')
            ->where('school_id', $tenant?->getKey())
            ->where('status', 'active')
            ->whereHas('student.guardianLinks.guardian', fn (Builder $query) => $query
                ->where('user_id', Filament::auth()->id()))
            ->get();

        $classIds = $placements->pluck('school_class_id')->filter()->unique();
        $sectionIds = $placements->pluck('class_section_id')->filter()->unique();
        $divisions = $placements->pluck('schoolClass.department')->filter()->unique();

        return Notice::query()
            ->with(['schoolClass', 'classSection', 'author'])
            ->where('school_id', $tenant?->getKey())
            ->published()
            ->where(function (Builder $query) use ($classIds, $sectionIds, $divisions): void {
                $query->where('audience_type', Notice::AUDIENCE_ALL);

                if ($divisions->isNotEmpty()) {
                    $query->orWhere(fn (Builder $query): Builder => $query
                        ->where('audience_type', Notice::AUDIENCE_DIVISION)
                        ->whereIn('audience_division', $divisions));
                }

                if ($classIds->isNotEmpty()) {
                    $query->orWhere(fn (Builder $query): Builder => $query
                        ->where('audience_type', Notice::AUDIENCE_CLASS)
                        ->whereIn('school_class_id', $classIds)
                        ->where(fn (Builder $query): Builder => $query
                            ->whereNull('class_section_id')
                            ->when($sectionIds->isNotEmpty(), fn (Builder $query): Builder => $query
                                ->orWhereIn('class_section_id', $sectionIds))));
                }
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();
    }
}
