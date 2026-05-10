<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Student;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add student'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All students'),
            'active' => Tab::make('Active')
                ->badge(fn (): int => Student::query()->where('status', 'active')->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'active')),
            'inactive' => Tab::make('Inactive')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'inactive')),
            'transferred' => Tab::make('Transferred')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'transferred')),
            'graduated' => Tab::make('Graduated')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'graduated')),
        ];
    }
}
