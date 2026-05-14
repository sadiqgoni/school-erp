<?php

namespace App\Filament\Resources\ResultTraitItems\Pages;

use App\Filament\Resources\ResultTraitItems\ResultTraitItemResource;
use App\Models\ResultTraitItem;
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

                    if (! $tenant) {
                        return;
                    }

                    $items = [
                        ['name' => 'Punctuality', 'category' => 'affective', 'position' => 1],
                        ['name' => 'Attendance', 'category' => 'affective', 'position' => 2],
                        ['name' => 'Neatness', 'category' => 'affective', 'position' => 3],
                        ['name' => 'Politeness', 'category' => 'affective', 'position' => 4],
                        ['name' => 'Leadership', 'category' => 'affective', 'position' => 5],
                        ['name' => 'Handwriting', 'category' => 'psychomotor', 'position' => 1],
                        ['name' => 'Drawing and craft', 'category' => 'psychomotor', 'position' => 2],
                        ['name' => 'Sports and games', 'category' => 'psychomotor', 'position' => 3],
                        ['name' => 'Verbal fluency', 'category' => 'psychomotor', 'position' => 4],
                    ];

                    foreach ($items as $item) {
                        ResultTraitItem::query()->firstOrCreate(
                            [
                                'school_id' => $tenant->getKey(),
                                'category' => $item['category'],
                                'name' => $item['name'],
                            ],
                            [
                                'max_rating' => 5,
                                'position' => $item['position'],
                                'is_active' => true,
                            ],
                        );
                    }

                    Notification::make()
                        ->title('Common result traits loaded')
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
