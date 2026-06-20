<?php

namespace App\Filament\Resources\TeachingAssignments\Pages;

use App\Filament\Resources\TeachingAssignments\TeachingAssignmentResource;
use App\Filament\Support\ClassTabs;
use App\Models\TeachingAssignment;
use App\Support\StaffSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTeachingAssignments extends ListRecords
{
    protected static string $resource = TeachingAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleAssignments')
                ->label('Sample assignments')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Create sample teacher assignments?')
                ->modalDescription('This will create a current session if needed, connect teachers to classes, and assign subject teachers.')
                ->action(function (): void {
                    $school = Filament::getTenant();

                    if (! $school) {
                        Notification::make()
                            ->title('Open a school section first')
                            ->warning()
                            ->send();

                        return;
                    }

                    $counts = StaffSampleSetup::createTeachingAssignments($school);

                    Notification::make()
                        ->title('Sample assignments ready')
                        ->body("{$counts['form']} form teacher assignments and {$counts['subjects']} subject assignments are available.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::direct(TeachingAssignment::class, 'All assignments');
    }
}
