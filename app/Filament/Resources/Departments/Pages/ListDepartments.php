<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Support\StaffSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDepartments extends ListRecords
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleDepartments')
                ->label('Sample departments')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Create sample departments?')
                ->modalDescription('This will add section-appropriate departments  without deleting existing records.')
                ->action(function (): void {
                    $school = Filament::getTenant();

                    if (! $school) {
                        Notification::make()
                            ->title('Open a school section first')
                            ->warning()
                            ->send();

                        return;
                    }

                    $count = StaffSampleSetup::createDepartments($school);

                    Notification::make()
                        ->title('Sample departments ready')
                        ->body("{$count} departments are available for this section.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
