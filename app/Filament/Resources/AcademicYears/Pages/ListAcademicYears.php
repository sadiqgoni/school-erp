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
use Illuminate\Support\Carbon;

class ListAcademicYears extends ListRecords
{
    protected static string $resource = AcademicYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleSession')
                ->label('Sample session')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->requiresConfirmation()
                ->modalHeading('Create sample academic session')
                ->modalDescription('This creates a Nigerian three-term academic year. Existing records with the same names will be updated.')
                ->action(function (): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    $startYear = now()->month >= 8 ? now()->year : now()->year - 1;
                    $endYear = $startYear + 1;
                    $sessionName = "{$startYear}/{$endYear}";

                    $terms = [
                        ['name' => 'First Term', 'starts_on' => "{$startYear}-09-08", 'ends_on' => "{$startYear}-12-12"],
                        ['name' => 'Second Term', 'starts_on' => "{$endYear}-01-12", 'ends_on' => "{$endYear}-04-03"],
                        ['name' => 'Third Term', 'starts_on' => "{$endYear}-04-27", 'ends_on' => "{$endYear}-07-24"],
                    ];

                    DB::transaction(function () use ($tenant, $sessionName, $startYear, $endYear, $terms): void {
                        AcademicYear::query()
                            ->where('school_id', $tenant->getKey())
                            ->update(['is_current' => false]);

                        $academicYear = AcademicYear::query()->updateOrCreate(
                            [
                                'school_id' => $tenant->getKey(),
                                'name' => $sessionName,
                            ],
                            [
                                'starts_on' => "{$startYear}-09-08",
                                'ends_on' => "{$endYear}-07-24",
                                'is_current' => true,
                                'is_active' => true,
                            ],
                        );

                        foreach ($terms as $index => $term) {
                            $startsOn = Carbon::parse($term['starts_on']);
                            $endsOn = Carbon::parse($term['ends_on']);

                            Term::query()->updateOrCreate(
                                [
                                    'school_id' => $tenant->getKey(),
                                    'academic_year_id' => $academicYear->getKey(),
                                    'name' => $term['name'],
                                ],
                                [
                                    'position' => $index + 1,
                                    'starts_on' => $startsOn,
                                    'ends_on' => $endsOn,
                                    'is_current' => $term['name'] === 'Third Term',
                                    'is_active' => true,
                                ],
                            );
                        }
                    });

                    Notification::make()
                        ->success()
                        ->title('Sample session ready')
                        ->body("Created {$sessionName} with First, Second, and Third Term.")
                        ->send();
                }),
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
                            ['name' => 'Third Term', 'is_current' => true],
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
                            $currentMarked = false;

                            foreach (collect($data['terms'] ?? [])->values() as $index => $term) {
                                $isCurrent = (bool) ($term['is_current'] ?? false) && ! $currentMarked;
                                $currentMarked = $currentMarked || $isCurrent;

                                Term::query()->create([
                                    'school_id' => $tenant->getKey(),
                                    'academic_year_id' => $academicYear->getKey(),
                                    'name' => $term['name'],
                                    'position' => $index + 1,
                                    'starts_on' => $term['starts_on'],
                                    'ends_on' => $term['ends_on'],
                                    'is_current' => $isCurrent,
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
