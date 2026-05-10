<?php

namespace App\Filament\Resources\AcademicYears\Pages;

use App\Filament\Resources\AcademicYears\AcademicYearResource;
use App\Models\AcademicYear;
use App\Models\Term;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListAcademicYears extends ListRecords
{
    protected static string $resource = AcademicYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quickSetup')
                ->label('Quick setup')
                ->icon('heroicon-o-bolt')
                ->color('primary')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->modalHeading('Create academic year')
                ->modalDescription('Create a new session and optionally set up the standard terms immediately.')
                ->modalWidth('5xl')
                ->schema([
                    TextInput::make('name')
                        ->label('Academic year')
                        ->placeholder('2026/2027')
                        ->required(),
                    DatePicker::make('starts_on')
                        ->required(),
                    DatePicker::make('ends_on')
                        ->required(),
                    Toggle::make('is_current')
                        ->label('Current academic year')
                        ->default(true),
                    Toggle::make('create_terms')
                        ->label('Create terms now')
                        ->default(true)
                        ->live(),
                    Repeater::make('terms')
                        ->visible(fn ($get): bool => (bool) $get('create_terms'))
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

                    DB::transaction(function () use ($tenant, $data): void {
                        if ($data['is_current'] ?? false) {
                            AcademicYear::query()
                                ->where('school_id', $tenant->getKey())
                                ->update(['is_current' => false]);
                        }

                        $academicYear = AcademicYear::query()->create([
                            'school_id' => $tenant->getKey(),
                            'name' => $data['name'],
                            'starts_on' => $data['starts_on'],
                            'ends_on' => $data['ends_on'],
                            'is_current' => (bool) ($data['is_current'] ?? false),
                            'is_active' => true,
                        ]);

                        if ($data['create_terms'] ?? false) {
                            foreach (collect($data['terms'] ?? [])->values() as $index => $term) {
                                Term::query()->create([
                                    'school_id' => $tenant->getKey(),
                                    'academic_year_id' => $academicYear->getKey(),
                                    'name' => $term['name'],
                                    'position' => $index + 1,
                                    'starts_on' => $term['starts_on'],
                                    'ends_on' => $term['ends_on'],
                                    'is_current' => (bool) ($term['is_current'] ?? false),
                                    'is_active' => true,
                                ]);
                            }
                        }
                    });

                    Notification::make()
                        ->success()
                        ->title('Academic year created')
                        ->body(($data['create_terms'] ?? false) ? 'Session and terms created successfully.' : 'Session created successfully.')
                        ->send();
                }),
            CreateAction::make()
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school'),
        ];
    }
}
