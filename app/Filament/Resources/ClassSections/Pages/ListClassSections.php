<?php

namespace App\Filament\Resources\ClassSections\Pages;

use App\Filament\Resources\ClassSections\ClassSectionResource;
use App\Filament\Support\ClassTabs;
use App\Models\ClassSection;
use App\Models\SchoolClass;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListClassSections extends ListRecords
{
    protected static string $resource = ClassSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleArms')
                ->label('Sample arms')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Create sample arms')
                ->modalDescription('This creates A and B arms for every class in this school section.')
                ->action(function (): void {
                    $tenant = Filament::getTenant();
                    $classes = SchoolClass::query()
                        ->where('school_id', $tenant?->getKey())
                        ->orderBy('level')
                        ->get();

                    if (! $tenant || $classes->isEmpty()) {
                        Notification::make()
                            ->warning()
                            ->title('Create classes first')
                            ->body('Generate sample classes before adding sample arms.')
                            ->send();

                        return;
                    }

                    $saved = 0;

                    foreach ($classes as $class) {
                        foreach (['A', 'B'] as $arm) {
                            ClassSection::query()->updateOrCreate(
                                [
                                    'school_id' => $tenant->getKey(),
                                    'school_class_id' => $class->getKey(),
                                    'code' => "{$class->code}-{$arm}",
                                ],
                                [
                                    'name' => $arm,
                                    'capacity' => 35,
                                    'is_active' => true,
                                ],
                            );

                            $saved++;
                        }
                    }

                    Notification::make()
                        ->success()
                        ->title('Sample arms ready')
                        ->body("Saved {$saved} class arms.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return ClassTabs::direct(ClassSection::class, 'All arms');
    }
}
