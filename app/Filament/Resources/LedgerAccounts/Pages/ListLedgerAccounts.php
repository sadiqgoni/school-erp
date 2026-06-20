<?php

namespace App\Filament\Resources\LedgerAccounts\Pages;

use App\Filament\Resources\LedgerAccounts\LedgerAccountResource;
use App\Support\FinanceSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListLedgerAccounts extends ListRecords
{
    protected static string $resource = LedgerAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleChartOfAccounts')
                ->label('Sample chart')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->requiresConfirmation()
                ->modalHeading('Create sample chart of accounts?')
                ->modalDescription('This will add asset, income, expense, liability, and equity accounts for school finance testing.')
                ->action(function (): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    $count = FinanceSampleSetup::createLedgerAccounts($tenant);

                    Notification::make()
                        ->success()
                        ->title('Sample chart ready')
                        ->body("{$count} ledger accounts are available for this section.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
