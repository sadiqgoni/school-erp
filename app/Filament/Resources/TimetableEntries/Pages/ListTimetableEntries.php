<?php

namespace App\Filament\Resources\TimetableEntries\Pages;

use App\Filament\Resources\TimetableEntries\TimetableEntryResource;
use App\Filament\Support\ClassTabs;
use App\Models\TimetableEntry;
use App\Support\TeacherWorkspace;
use App\Support\TimetableSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTimetableEntries extends ListRecords
{
    protected static string $resource = TimetableEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleTimetable')
                ->label('Load sample timetable')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Generate a sample timetable?')
                ->modalDescription('This fills each class with a ready-made weekly timetable that you can edit afterwards.')
                ->action(function (): void {
                    $school = Filament::getTenant();

                    if (! $school) {
                        Notification::make()
                            ->title('Open a school section first')
                            ->warning()
                            ->send();

                        return;
                    }

                    $result = TeacherWorkspace::isTeacher()
                        ? TimetableSampleSetup::createForTeacher($school)
                        : TimetableSampleSetup::createForSchool($school);

                    Notification::make()
                        ->title('Sample timetable ready')
                        ->body("Created or refreshed {$result['entries']} periods across {$result['targets']} timetable(s).")
                        ->success()
                        ->send();
                }),
            CreateAction::make()
                ->label('Add period')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::direct(TimetableEntry::class, 'All periods');
    }
}
