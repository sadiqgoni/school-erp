<?php

namespace App\Filament\Resources\StudentDiscounts\Schemas;

use App\Filament\Support\SchoolSelect;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StudentDiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Discount')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Examples: Scholarship, Sibling Discount, Staff Child Discount.'),
                        Select::make('type')
                            ->required()
                            ->default('fixed')
                            ->options([
                                'fixed' => 'Fixed amount',
                                'percentage' => 'Percentage',
                            ]),
                        TextInput::make('value')->numeric()->required()->prefix('NGN / %'),
                        Select::make('student_id')
                            ->label('Student')
                            ->options(fn (): array => Student::query()
                                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                ->orderBy('last_name')
                                ->orderBy('first_name')
                                ->get()
                                ->mapWithKeys(fn (Student $student): array => [$student->getKey() => trim("{$student->admission_number} - {$student->last_name} {$student->first_name}")])
                                ->all())
                            ->searchable()
                            ->preload(),
                        Select::make('school_class_id')
                            ->label('Class')
                            ->options(fn (): array => SchoolClass::query()
                                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                ->orderBy('level')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->helperText('Leave student empty to apply to the whole class.'),
                        Select::make('academic_year_id')
                            ->label('Academic year')
                            ->options(fn (): array => AcademicYear::query()
                                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                ->orderByDesc('starts_on')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload(),
                        Select::make('term_id')
                            ->label('Term')
                            ->options(fn (): array => Term::query()
                                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                ->orderBy('position')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload(),
                        DatePicker::make('starts_on'),
                        DatePicker::make('ends_on'),
                        Toggle::make('is_active')->default(true),
                        Textarea::make('notes')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
