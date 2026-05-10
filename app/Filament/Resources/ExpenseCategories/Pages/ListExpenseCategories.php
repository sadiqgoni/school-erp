<?php

namespace App\Filament\Resources\ExpenseCategories\Pages;

use App\Filament\Resources\ExpenseCategories\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListExpenseCategories extends ListRecords
{
    protected static string $resource = ExpenseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateCommonExpenseCategories')
                ->label('Generate common categories')
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
                        'Salaries',
                        'Fuel & Transport',
                        'Repairs & Maintenance',
                        'Stationery & Printing',
                        'Utilities',
                        'Events & Ceremonies',
                        'Teaching Materials',
                        'Security',
                    ] as $name) {
                        ExpenseCategory::query()->updateOrCreate(
                            ['school_id' => $tenant->getKey(), 'name' => $name],
                            ['description' => null, 'is_active' => true],
                        );
                        $count++;
                    }

                    Notification::make()
                        ->success()
                        ->title('Expense categories created')
                        ->body("Prepared {$count} common expense categories for this school.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
