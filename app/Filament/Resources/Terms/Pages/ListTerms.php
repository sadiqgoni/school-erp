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
use Illuminate\Support\Carbon;

class ListTerms extends ListRecords
{
    protected static string $resource = TermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleTerms')
                ->label('Sample terms')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->requiresConfirmation()
                ->modalHeading('Create sample terms')
                ->modalDescription('This creates First, Second, and Third Term for the current academic year.')
                ->action(function (): void {
                    $tenant = Filament::getTenant();
                    $academicYear = AcademicYear::query()
                        ->where('school_id', $tenant?->getKey())
                        ->where('is_current', true)
                        ->first()
                        ?? AcademicYear::query()
                            ->where('school_id', $tenant?->getKey())
                            ->latest('starts_on')
                            ->first();

                    if (! $tenant || ! $academicYear) {
                        Notification::make()
                            ->warning()
                            ->title('Create a session first')
                            ->body('Create an academic year before generating sample terms.')
                            ->send();

                        return;
                    }

                    $startYear = (int) $academicYear->starts_on?->format('Y') ?: (now()->month >= 8 ? now()->year : now()->year - 1);
                    $endYear = $startYear + 1;
                    $terms = [
                        ['name' => 'First Term', 'starts_on' => "{$startYear}-09-08", 'ends_on' => "{$startYear}-12-12"],
                        ['name' => 'Second Term', 'starts_on' => "{$endYear}-01-12", 'ends_on' => "{$endYear}-04-03"],
                        ['name' => 'Third Term', 'starts_on' => "{$endYear}-04-27", 'ends_on' => "{$endYear}-07-24"],
                    ];

                    Term::query()
                        ->where('school_id', $tenant->getKey())
                        ->where('academic_year_id', $academicYear->getKey())
                        ->update(['is_current' => false]);

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

                    Notification::make()
                        ->success()
                        ->title('Sample terms ready')
                        ->body("Saved three terms for {$academicYear->name}.")
                        ->send();
                }),
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

                    Term::query()
                        ->where('school_id', $tenant->getKey())
                        ->where('academic_year_id', $data['academic_year_id'])
                        ->update(['is_current' => false]);

                    $currentMarked = false;

                    foreach (collect($data['terms'] ?? [])->values() as $index => $term) {
                        $isCurrent = (bool) ($term['is_current'] ?? false) && ! $currentMarked;
                        $currentMarked = $currentMarked || $isCurrent;

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
                                'is_current' => $isCurrent,
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
