<?php

namespace App\Filament\Resources\UserActivities\Pages;

use App\Filament\Resources\UserActivities\UserActivityResource;
use App\Models\UserActivity;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUserActivities extends ListRecords
{
    protected static string $resource = UserActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * One tab per school with the most activity recently — "where is the
     * activity going on" at a glance — capped so this stays usable once
     * there are hundreds of schools rather than growing one tab per school.
     */
    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All')
                ->badge(UserActivity::query()->count()),
            'platform' => Tab::make('Platform')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('school_id'))
                ->badge(UserActivity::query()->whereNull('school_id')->count()),
        ];

        $mostActiveSchools = UserActivity::query()
            ->whereNotNull('school_id')
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('school_id, count(*) as activity_count')
            ->groupBy('school_id')
            ->orderByDesc('activity_count')
            ->limit(8)
            ->with('school:id,name')
            ->get();

        foreach ($mostActiveSchools as $row) {
            $schoolId = $row->school_id;

            $tabs['school-'.$schoolId] = Tab::make($row->school?->name ?? "School #{$schoolId}")
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('school_id', $schoolId))
                ->badge((string) $row->activity_count);
        }

        return $tabs;
    }
}
