<?php

namespace App\Filament\Resources\StudentDiscounts\Pages;

use App\Filament\Resources\StudentDiscounts\StudentDiscountResource;
use App\Filament\Support\ClassTabs;
use App\Models\StudentDiscount;
use App\Support\FinanceSampleSetup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListStudentDiscounts extends ListRecords
{
    protected static string $resource = StudentDiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleDiscounts')
                ->label('Sample discounts')
                ->icon('heroicon-m-sparkles')
                ->color('success')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->requiresConfirmation()
                ->modalHeading('Create sample discounts?')
                ->modalDescription('This will add common class-level discounts for finance testing.')
                ->action(function (): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    $count = FinanceSampleSetup::createStudentDiscounts($tenant);

                    Notification::make()
                        ->success()
                        ->title('Sample discounts ready')
                        ->body("{$count} discounts are available for this section.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::directOrStudentEnrollment(StudentDiscount::class, 'All discounts');
    }
}
