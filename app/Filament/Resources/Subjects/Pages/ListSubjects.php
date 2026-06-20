<?php

namespace App\Filament\Resources\Subjects\Pages;

use App\Filament\Resources\Subjects\SubjectResource;
use App\Models\Subject;
use App\Support\SubjectPreset;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Set;

class ListSubjects extends ListRecords
{
    protected static string $resource = SubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sampleSubjects')
                ->label('Sample subjects')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->requiresConfirmation()
                ->modalHeading('Create sample subjects')
                ->modalDescription('This creates a starter Nigerian subject list for the current school section.')
                ->action(function (): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    $subjects = collect(SubjectPreset::defaults(
                        SubjectPreset::defaultTemplatesForDivision($tenant->division),
                    ));

                    if ($subjects->isEmpty()) {
                        $subjects = collect(SubjectPreset::defaults(['nursery', 'primary', 'junior_secondary', 'senior_secondary', 'common']));
                    }

                    $saved = $subjects->map(fn (array $subject) => Subject::query()->updateOrCreate(
                        [
                            'school_id' => $tenant->getKey(),
                            'code' => $subject['code'],
                        ],
                        [
                            'name' => $subject['name'],
                            'department' => $subject['department'] ?: null,
                            'is_active' => true,
                        ],
                    ));

                    Notification::make()
                        ->success()
                        ->title('Sample subjects ready')
                        ->body("Saved {$saved->count()} subjects.")
                        ->send();
                }),
            Action::make('generateSubjects')
                ->label('Generate subjects')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->modalHeading('Generate subjects')
                ->modalDescription('Create a starter subject list for nursery, primary, and secondary levels, then edit it anytime.')
                ->modalWidth('7xl')
                ->schema([
                    CheckboxList::make('templates')
                        ->options(fn (): array => SubjectPreset::optionsForDivision(Filament::getTenant()?->division))
                        ->default(fn (): array => SubjectPreset::defaultTemplatesForDivision(Filament::getTenant()?->division))
                        ->columns(2)
                        ->live()
                        ->afterStateUpdated(function (?array $state, Set $set): void {
                            $set('subjects', SubjectPreset::defaults($state ?? []));
                        })
                        ->helperText('Only subject sets for the selected school section are shown.'),
                    Repeater::make('subjects')
                        ->default(fn (): array => SubjectPreset::defaults(
                            SubjectPreset::defaultTemplatesForDivision(Filament::getTenant()?->division),
                        ))
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(120),
                            TextInput::make('code')
                                ->required()
                                ->maxLength(30),
                            TextInput::make('department')
                                ->label('Category')
                                ->maxLength(255),
                        ])
                        ->columns(2)
                        ->addActionLabel('Add subject')
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                ])
                ->action(function (array $data): void {
                    $tenant = Filament::getTenant();

                    if (! $tenant) {
                        return;
                    }

                    $subjects = collect($data['subjects'] ?? [])
                        ->filter(fn (array $subject): bool => filled($subject['name'] ?? null) && filled($subject['code'] ?? null))
                        ->values();

                    $saved = $subjects->map(function (array $subject) use ($tenant) {
                        return Subject::query()->updateOrCreate(
                            [
                                'school_id' => $tenant->getKey(),
                                'code' => $subject['code'],
                            ],
                            [
                                'name' => $subject['name'],
                                'department' => $subject['department'] ?: null,
                                'is_active' => true,
                            ],
                        );
                    });

                    Notification::make()
                        ->success()
                        ->title('Subjects created')
                        ->body("Saved {$saved->count()} subjects.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
