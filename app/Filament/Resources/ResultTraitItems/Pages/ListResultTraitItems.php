<?php

namespace App\Filament\Resources\ResultTraitItems\Pages;

use App\Filament\Resources\ResultTraitItems\ResultTraitItemResource;
use App\Models\School;
use App\Support\ResultTraitSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListResultTraitItems extends ListRecords
{
    protected static string $resource = ResultTraitItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('loadCommonTraits')
                ->label('Load common traits')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->action(function (): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant instanceof School) {
                        return;
                    }

                    ResultTraitSampleSetup::ensureForSchool($tenant);

                    Notification::make()
                        ->title('Common result traits loaded')
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
