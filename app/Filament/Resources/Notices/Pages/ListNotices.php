<?php

namespace App\Filament\Resources\Notices\Pages;

use App\Filament\Resources\Notices\NoticeResource;
use App\Filament\Support\ClassTabs;
use App\Models\Notice;
use App\Models\School;
use App\Support\NoticeSampleSetup;
use App\Support\TeacherWorkspace;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListNotices extends ListRecords
{
    protected static string $resource = NoticeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleNotices')
                ->label('Load sample data')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->visible(fn (): bool => ! TeacherWorkspace::isTeacher())
                ->requiresConfirmation()
                ->modalHeading('Create sample notices?')
                ->modalDescription('Adds a set of realistic, ready-to-edit notices (resumption, PTA meeting, fee reminder, Sallah break, and more) so the notice board and parent portal aren\'t empty.')
                ->action(function (): void {
                    $school = Filament::getTenant();

                    if (! $school instanceof School) {
                        Notification::make()
                            ->title('Open a school section first')
                            ->warning()
                            ->send();

                        return;
                    }

                    $count = NoticeSampleSetup::createForSchool($school, Filament::auth()->id());

                    Notification::make()
                        ->title('Sample notices ready')
                        ->body("Saved {$count} notices.")
                        ->success()
                        ->send();
                }),
            CreateAction::make()
                ->label(TeacherWorkspace::isTeacher() ? 'New class announcement' : 'New notice')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::direct(Notice::class, 'All notices');
    }
}
