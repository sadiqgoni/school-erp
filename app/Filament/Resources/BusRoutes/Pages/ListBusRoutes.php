<?php

namespace App\Filament\Resources\BusRoutes\Pages;

use App\Filament\Resources\BusRoutes\BusRouteResource;
use App\Models\School;
use App\Support\SafetyTransportSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListBusRoutes extends ListRecords
{
    protected static string $resource = BusRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleRoutes')
                ->label('Load sample data')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Create sample bus routes?')
                ->modalDescription('This creates sample routes, student route assignments, NFC devices, and movements so the transport pages feel populated immediately.')
                ->action(function (): void {
                    $school = Filament::getTenant();

                    if (! $school instanceof School) {
                        Notification::make()
                            ->title('Open a school section first')
                            ->warning()
                            ->send();

                        return;
                    }

                    $result = SafetyTransportSampleSetup::createForSchool($school);

                    if ($result['routes'] === 0) {
                        Notification::make()
                            ->title('No active students yet')
                            ->body('Create or load sample students first, then try again.')
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Sample transport data ready')
                        ->body("Saved {$result['routes']} routes, {$result['assignments']} route assignments, and {$result['movements']} movements.")
                        ->success()
                        ->send();
                }),
            CreateAction::make()
                ->label('New route')
                ->icon('heroicon-o-plus'),
        ];
    }
}
