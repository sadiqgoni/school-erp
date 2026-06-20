<?php

namespace App\Filament\Resources\StaffRoles\Pages;

use App\Filament\Resources\StaffRoles\StaffRoleResource;
use App\Support\StaffSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListStaffRoles extends ListRecords
{
    protected static string $resource = StaffRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleRoles')
                ->label('Sample roles')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Create sample staff roles?')
                ->modalDescription('This will add common school roles for this section without deleting existing records.')
                ->action(function (): void {
                    $school = Filament::getTenant();

                    if (! $school) {
                        Notification::make()
                            ->title('Open a school section first')
                            ->warning()
                            ->send();

                        return;
                    }

                    $count = StaffSampleSetup::createStaffRoles($school);

                    Notification::make()
                        ->title('Sample roles ready')
                        ->body("{$count} roles are available for this section.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
