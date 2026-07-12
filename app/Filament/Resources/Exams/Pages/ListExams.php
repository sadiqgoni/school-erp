<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Filament\Resources\Exams\ExamResource;
use App\Models\School;
use App\Support\ExamResultsSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListExams extends ListRecords
{
    protected static string $resource = ExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleResults')
                ->label('Load sample data')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Create a sample exam with results?')
                ->modalDescription('Generates a term examination, continuous assessment and main exam scores for every enrolled student, then compiles and publishes report cards — using the same grading engine as a real exam.')
                ->action(function (): void {
                    $school = Filament::getTenant();

                    if (! $school instanceof School) {
                        Notification::make()
                            ->title('Open a school section first')
                            ->warning()
                            ->send();

                        return;
                    }

                    $result = ExamResultsSampleSetup::createForSchool($school);

                    if ($result['students'] === 0) {
                        Notification::make()
                            ->title('Nothing to score yet')
                            ->body('Generate sample students, teaching assignments, and class subjects first, then try again.')
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Sample results ready')
                        ->body("Scored {$result['students']} students ({$result['scores']} score entries) and published {$result['reportCards']} report cards.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
