<?php

namespace App\Filament\Resources\Terms\Pages;

use App\Filament\Resources\Terms\TermResource;
use App\Models\AcademicYear;
use App\Models\Term;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTerms extends ListRecords
{
    protected static string $resource = TermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quickSetup')
                ->label('Quick setup')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->modalHeading('Create terms')
                ->modalDescription('Create the term structure for an academic year in one go.')
                ->modalWidth('5xl')
                ->schema([
                    Select::make('academic_year_id')
                        ->label('Academic year')
                        ->options(fn (): array => AcademicYear::query()->orderByDesc('starts_on')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                    Repeater::make('terms')
                        ->default([
                            ['name' => 'First Term'],
                            ['name' => 'Second Term'],
                            ['name' => 'Third Term'],
                        ])
                        ->schema([
                            TextInput::make('name')
                                ->required(),
                            DatePicker::make('starts_on')
                                ->required(),
                            DatePicker::make('ends_on')
                                ->required(),
                            Toggle::make('is_current')
                                ->default(false),
                        ])
                        ->columns(2)
                        ->addActionLabel('Add term'),
                ])
                ->action(function (array $data): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    foreach (collect($data['terms'] ?? [])->values() as $index => $term) {
                        Term::query()->updateOrCreate(
                            [
                                'school_id' => $tenant->getKey(),
                                'academic_year_id' => $data['academic_year_id'],
                                'name' => $term['name'],
                            ],
                            [
                                'position' => $index + 1,
                                'starts_on' => $term['starts_on'],
                                'ends_on' => $term['ends_on'],
                                'is_current' => (bool) ($term['is_current'] ?? false),
                                'is_active' => true,
                            ],
                        );
                    }

                    Notification::make()
                        ->success()
                        ->title('Terms created')
                        ->body('The academic year terms have been saved.')
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
