<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use App\Models\Staff;
use App\Support\StaffSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleStaff')
                ->label('Sample staff')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Create sample staff?')
                ->modalDescription('This will add directors, academic leaders, teachers, and admin staff .')
                ->action(function (): void {
                    $school = Filament::getTenant();

                    if (! $school) {
                        Notification::make()
                            ->title('Open a school section first')
                            ->warning()
                            ->send();

                        return;
                    }

                    $count = StaffSampleSetup::createStaff($school);

                    Notification::make()
                        ->title('Sample staff ready')
                        ->body("{$count} staff records are available for this section.")
                        ->success()
                        ->send();
                }),
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
