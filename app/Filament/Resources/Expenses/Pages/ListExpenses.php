<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Resources\Expenses\ExpenseResource;
use App\Support\FinanceSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleExpenses')
                ->label('Sample expenses')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->requiresConfirmation()
                ->modalHeading('Create sample expenses?')
                ->modalDescription('This will add approved sample expenses and post them to the chart of accounts.')
                ->action(function (): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    $count = FinanceSampleSetup::createExpenses($tenant);

                    Notification::make()
                        ->success()
                        ->title('Sample expenses ready')
                        ->body("{$count} expenses are available and posted for this section.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
