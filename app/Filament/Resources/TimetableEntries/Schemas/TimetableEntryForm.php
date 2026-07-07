<?php

namespace App\Filament\Resources\TimetableEntries\Schemas;

use App\Filament\Support\SchoolSelect;
use App\Models\ClassSection;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TimetableEntry;
use App\Support\TeacherWorkspace;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TimetableEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Timetable period')
                    ->description('Add one period at a time. Parents see the finished weekly grid.')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('school_class_id')
                            ->label('Class')
                            ->options(fn (): array => self::classOptions())
                            ->default(fn (): ?int => TeacherWorkspace::lockedFormClassId())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->disabled(fn (): bool => TeacherWorkspace::shouldLockToFormAssignment())
                            ->dehydrated()
                            ->helperText(fn (): ?string => TeacherWorkspace::shouldLockToFormAssignment()
                                ? 'Locked to your assigned form class.'
                                : null)
                            ->required(),
                        Select::make('class_section_id')
                            ->label('Arm (optional)')
                            ->default(fn (): ?int => TeacherWorkspace::lockedFormSectionId())
                            ->options(fn (Get $get): array => ClassSection::query()
                                ->where('school_class_id', $get('school_class_id') ?: 0)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->placeholder('Whole class')
                            ->disabled(fn (): bool => TeacherWorkspace::shouldLockToFormAssignment())
                            ->dehydrated()
                            ->helperText(fn (): ?string => TeacherWorkspace::shouldLockToFormAssignment()
                                ? 'Locked to your assigned arm.'
                                : null),
                        Select::make('day_of_week')
                            ->label('Day')
                            ->options(TimetableEntry::DAYS)
                            ->required(),
                        TextInput::make('period_number')
                            ->label('Period')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(15)
                            ->required(),
                        TimePicker::make('starts_at')
                            ->label('Starts')
                            ->seconds(false),
                        TimePicker::make('ends_at')
                            ->label('Ends')
                            ->seconds(false)
                            ->after('starts_at'),
                        Select::make('entry_type')
                            ->label('Type')
                            ->options([
                                TimetableEntry::TYPE_LESSON => 'Lesson',
                                TimetableEntry::TYPE_BREAK => 'Break / Assembly',
                            ])
                            ->default(TimetableEntry::TYPE_LESSON)
                            ->live()
                            ->required(),
                        Select::make('subject_id')
                            ->label('Subject')
                            ->options(fn (): array => Subject::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('entry_type') !== TimetableEntry::TYPE_BREAK),
                        TextInput::make('label')
                            ->label(fn (Get $get): string => $get('entry_type') === TimetableEntry::TYPE_BREAK
                                ? 'Break name (e.g. Short break, Assembly, Lunch)'
                                : 'Custom label (optional)')
                            ->maxLength(120),
                    ])
                    ->columns(2),
            ]);
    }

    protected static function classOptions(): array
    {
        $query = SchoolClass::query()->where('is_active', true)->orderBy('name');

        if (TeacherWorkspace::isTeacher()) {
            $query->whereKey(TeacherWorkspace::formClassIds() ?: [0]);
        }

        return $query->pluck('name', 'id')->all();
    }
}
