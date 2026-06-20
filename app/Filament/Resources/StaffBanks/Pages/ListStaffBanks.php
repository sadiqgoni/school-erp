<?php

namespace App\Filament\Resources\StaffBanks\Pages;

use App\Filament\Resources\StaffBanks\StaffBankResource;
use App\Models\StaffBank;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListStaffBanks extends ListRecords
{
    protected static string $resource = StaffBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleBanks')
                ->label('Sample banks')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->requiresConfirmation()
                ->modalHeading('Create sample staff banks?')
                ->modalDescription('This will add common Nigerian banks for staff salary records.')
                ->action(function (): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    $count = 0;

                    foreach (['Access Bank', 'First Bank', 'GTBank', 'UBA', 'Zenith Bank', 'Sterling Bank', 'Jaiz Bank', 'Stanbic IBTC'] as $name) {
                        StaffBank::query()->updateOrCreate(
                            ['school_id' => $tenant->getKey(), 'name' => $name],
                            ['is_active' => true, 'notes' => 'Sample staff salary bank.'],
                        );

                        $count++;
                    }

                    Notification::make()
                        ->success()
                        ->title('Sample banks ready')
                        ->body("{$count} staff banks are available.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
