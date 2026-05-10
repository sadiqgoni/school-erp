<?php

namespace App\Filament\Resources\TeachingAssignments\Schemas;

use App\Filament\Support\SchoolSelect;
use App\Models\ClassSection;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\TeachingAssignment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TeachingAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Teaching assignment')
                    ->schema([
                        SchoolSelect::make(),
                        Select::make('staff_id')
                            ->label('Teacher')
                            ->relationship(
                                'staff',
                                'staff_number',
                                modifyQueryUsing: fn ($query) => $query
                                    ->where('staff_type', Staff::TYPE_TEACHING)
                                    ->orderBy('last_name')
                                    ->orderBy('first_name'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Staff $record): string => "{$record->full_name} ({$record->staff_number})")
                            ->searchable()
                            ->preload()
                            ->default(request()->integer('staff_id') ?: null)
                            ->required(),
                        Select::make('academic_year_id')
                            ->relationship('academicYear', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('term_id')
                            ->relationship('term', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('school_class_id')
                            ->relationship('schoolClass', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        Select::make('class_section_id')
                            ->label('Arm')
                            ->options(fn (Get $get): array => ClassSection::query()
                                ->when($get('school_class_id'), fn ($query, $classId) => $query->where('school_class_id', $classId))
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (ClassSection $arm): array => [$arm->getKey() => "{$arm->schoolClass?->name} {$arm->name}"])
                                ->all())
                            ->searchable()
                            ->visible(fn (Get $get): bool => SchoolClass::query()->whereKey($get('school_class_id'))->exists()),
                        Select::make('assignment_role')
                            ->label('Assignment role')
                            ->options([
                                TeachingAssignment::ROLE_FORM_TEACHER => 'Form teacher',
                                TeachingAssignment::ROLE_ASSISTANT_FORM_TEACHER => 'Assistant form teacher',
                            ])
                            ->default(TeachingAssignment::ROLE_FORM_TEACHER)
                            ->required(),
                        Toggle::make('is_class_teacher')
                            ->label('Primary class teacher'),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
