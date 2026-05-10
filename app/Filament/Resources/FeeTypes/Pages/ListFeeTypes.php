<?php

namespace App\Filament\Resources\FeeTypes\Pages;

use App\Filament\Resources\FeeTypes\FeeTypeResource;
use App\Models\FeeType;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListFeeTypes extends ListRecords
{
    protected static string $resource = FeeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateCommonFeeTypes')
                ->label('Generate common fee types')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->action(function (): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    $count = 0;

                    foreach ([
                        ['name' => 'Tuition', 'is_required' => true],
                        ['name' => 'PTA Levy', 'is_required' => true],
                        ['name' => 'Development Levy', 'is_required' => true],
                        ['name' => 'Examination Fee', 'is_required' => true],
                        ['name' => 'Books', 'is_required' => false],
                        ['name' => 'Uniform', 'is_required' => false],
                        ['name' => 'Transport', 'is_required' => false],
                        ['name' => 'Hostel', 'is_required' => false],
                    ] as $feeType) {
                        FeeType::query()->updateOrCreate(
                            ['school_id' => $tenant->getKey(), 'name' => $feeType['name']],
                            $feeType + ['description' => null, 'is_active' => true],
                        );
                        $count++;
                    }

                    Notification::make()
                        ->success()
                        ->title('Fee types created')
                        ->body("Prepared {$count} common fee types for this school.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
