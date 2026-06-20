<?php

namespace App\Filament\Resources\FeeStructures\Pages;

use App\Filament\Resources\FeeStructures\FeeStructureResource;
use App\Filament\Support\ClassTabs;
use App\Models\FeeStructure;
use App\Support\FinanceSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListFeeStructures extends ListRecords
{
    protected static string $resource = FeeStructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleFeeStructures')
                ->label('Sample fee structures')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->requiresConfirmation()
                ->modalHeading('Create sample fee structures?')
                ->modalDescription('This will add termly fee amounts for every class in this section.')
                ->action(function (): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    $count = FinanceSampleSetup::createFeeStructures($tenant);

                    Notification::make()
                        ->success()
                        ->title('Sample fee structures ready')
                        ->body("{$count} fee structure rows are available for this section.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::direct(FeeStructure::class, 'All fee structures');
    }
}
