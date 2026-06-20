<?php

namespace App\Filament\Resources\FeeTypes\Pages;

use App\Filament\Resources\FeeTypes\FeeTypeResource;
use App\Support\FinanceSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListFeeTypes extends ListRecords
{
    protected static string $resource = FeeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleFeeTypes')
                ->label('Sample billing items')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->requiresConfirmation()
                ->modalHeading('Create sample billing items?')
                ->modalDescription('This will add section-appropriate billing items without deleting existing records.')
                ->action(function (): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    $count = FinanceSampleSetup::createFeeTypes($tenant);

                    Notification::make()
                        ->success()
                        ->title('Sample billing items ready')
                        ->body("{$count} billing items are available for this section.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
