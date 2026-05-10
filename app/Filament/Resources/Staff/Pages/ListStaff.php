<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use App\Models\Staff;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add staff'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All staff'),
            'teaching' => Tab::make('Teaching')
                ->badge(fn (): int => Staff::query()->where('staff_type', Staff::TYPE_TEACHING)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('staff_type', Staff::TYPE_TEACHING)),
            'non_teaching' => Tab::make('Non-teaching')
                ->badge(fn (): int => Staff::query()->where('staff_type', Staff::TYPE_NON_TEACHING)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('staff_type', Staff::TYPE_NON_TEACHING)),
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'active')),
        ];
    }
}
