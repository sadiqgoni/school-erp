<?php

namespace App\Filament\Resources\BankAccounts\Pages;

use App\Filament\Resources\BankAccounts\BankAccountResource;
use App\Support\FinanceSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListBankAccounts extends ListRecords
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleBankAccounts')
                ->label('Sample bank accounts')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->requiresConfirmation()
                ->modalHeading('Create sample bank accounts?')
                ->modalDescription('This will add fee collection and operations bank accounts .')
                ->action(function (): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    $count = FinanceSampleSetup::createBankAccounts($tenant);

                    Notification::make()
                        ->success()
                        ->title('Sample bank accounts ready')
                        ->body("{$count} bank accounts are available for this section.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
