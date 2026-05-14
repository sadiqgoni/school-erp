<?php

namespace App\Filament\Resources\SchoolClasses\Pages;

use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Models\SchoolClass;
use App\Support\SchoolStructurePreset;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;

class ListSchoolClasses extends ListRecords
{
    protected static string $resource = SchoolClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateStructure')
                ->label('Generate structure')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->button()
                ->modalHeading('Generate class structure')
                ->modalDescription('Create nursery, primary, secondary, grade, or Cambridge class levels in one pass, then edit them if needed.')
                ->modalWidth('7xl')
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'school')
                ->schema([
                    CheckboxList::make('templates')
                        ->options(fn (): array => SchoolStructurePreset::optionsForDivision(Filament::getTenant()?->division))
                        ->default(fn (): array => SchoolStructurePreset::defaultTemplatesForDivision(Filament::getTenant()?->division))
                        ->columns(2)
                        ->live()
                        ->afterStateUpdated(function (?array $state, Set $set): void {
                            $set('classes', SchoolStructurePreset::defaults($state ?? []));
                        })
                        ->helperText('Only structures for the selected school section are shown.'),
                    Repeater::make('classes')
                        ->label('Class setup')
                        ->default(fn (): array => SchoolStructurePreset::defaults(
                            SchoolStructurePreset::defaultTemplatesForDivision(Filament::getTenant()?->division),
                        ))
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(80),
                            TextInput::make('code')
                                ->required()
                                ->maxLength(30),
                            TextInput::make('level')
                                ->numeric()
                                ->required()
                                ->minValue(1),
                            TextInput::make('department')
                                ->label('Section')
                                ->maxLength(255),
                        ])
                        ->columns(2)
                        ->addActionLabel('Add class')
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

                    $classes = collect($data['classes'] ?? [])
                        ->filter(fn (array $class): bool => filled($class['name'] ?? null) && filled($class['code'] ?? null))
                        ->values();

                    $createdClasses = $classes->map(function (array $class) use ($tenant) {
                        return SchoolClass::query()->updateOrCreate(
                            [
                                'school_id' => $tenant->getKey(),
                                'code' => $class['code'],
                            ],
                            [
                                'name' => $class['name'],
                                'level' => $class['level'],
                                'department' => $class['department'] ?: null,
                                'is_active' => true,
                            ],
                        );
                    });

                    Notification::make()
                        ->success()
                        ->title('Structure created')
                        ->body("Saved {$createdClasses->count()} classes. Use the Arms action beside each class to add class arms.")
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All'),
        ];

        SchoolClass::query()
            ->select('department')
            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->each(function (string $section) use (&$tabs): void {
                $tabs[$section] = Tab::make($section)
                    ->modifyQueryUsing(fn ($query) => $query->where('department', $section));
            });

        return $tabs;
    }
}
