<?php

namespace App\Filament\Resources\PayrollItemTypes\Pages;

use App\Filament\Resources\PayrollItemTypes\PayrollItemTypeResource;
use App\Support\FinanceSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPayrollItemTypes extends ListRecords
{
    protected static string $resource = PayrollItemTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('samplePayrollElements')
                ->label('Sample payroll elements')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Create sample payroll elements?')
                ->modalDescription('This will also create the linked payroll chart of account entries needed for testing.')
                ->action(function (): void {
                    $school = Filament::getTenant();

                    if (! $school) {
                        return;
                    }

                    $count = FinanceSampleSetup::createPayrollElements($school);

                    Notification::make()
                        ->success()
                        ->title('Sample payroll elements ready')
                        ->body("{$count} payroll elements are available for this section.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
