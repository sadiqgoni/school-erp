<?php

namespace App\Filament\Resources\Students\Schemas;

use App\Filament\Support\SchoolSelect;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\SchoolClass;
use App\Models\Term;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Wizard\Step::make('Student Details')
                        ->description('Capture the pupil or student bio-data.')
                        ->schema([
                            SchoolSelect::make(),
                            TextInput::make('admission_number')
                                ->label('Admission number')
                                ->required()
                                ->maxLength(50)
                                ->helperText('Use the school admission number or generated student ID.'),
                            TextInput::make('first_name')
                                ->required()
                                ->maxLength(80),
                            TextInput::make('middle_name')
                                ->maxLength(80),
                            TextInput::make('last_name')
                                ->required()
                                ->maxLength(80),
                            DatePicker::make('date_of_birth')
                                ->required(),
                            Select::make('gender')
                                ->required()
                                ->options([
                                    'male' => 'Male',
                                    'female' => 'Female',
                                ]),
                            Select::make('status')
                                ->required()
                                ->default('active')
                                ->options([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                    'graduated' => 'Graduated',
                                    'transferred' => 'Transferred',
                                    'suspended' => 'Suspended',
                                ]),
                            DatePicker::make('admitted_on')
                                ->label('Admission date')
                                ->default(today()),
                            TextInput::make('previous_school')
                                ->label('Previous school')
                                ->maxLength(255),
                            FileUpload::make('photo_path')
                                ->label('Student photo')
                                ->image()
                                ->imageEditor()
                                ->disk('public')
                                ->directory('students/photos')
                                ->visibility('public')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Wizard\Step::make('Parent / Guardian')
                        ->description('Save parent or guardian contacts used for fees, communication, and emergencies.')
                        ->schema([
                            Repeater::make('guardian_entries')
                                ->label('Parents / Guardians')
                                ->defaultItems(1)
                                ->minItems(1)
                                ->maxItems(3)
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Full name')
                                        ->required()
                                        ->maxLength(255),
                                    Select::make('relationship')
                                        ->required()
                                        ->default('mother')
                                        ->options([
                                            'father' => 'Father',
                                            'mother' => 'Mother',
                                            'guardian' => 'Guardian',
                                            'uncle' => 'Uncle',
                                            'aunt' => 'Aunt',
                                            'sibling' => 'Sibling',
                                            'other' => 'Other',
                                        ]),
                                    TextInput::make('phone')
                                        ->label('Primary phone')
                                        ->tel()
                                        ->required()
                                        ->maxLength(30),
                                    TextInput::make('alternate_phone')
                                        ->label('Alternate phone')
                                        ->tel()
                                        ->maxLength(30),
                                    TextInput::make('email')
                                        ->email()
                                        ->maxLength(255),
                                    TextInput::make('occupation')
                                        ->maxLength(255),
                                    Textarea::make('address')
                                        ->columnSpanFull(),
                                    Select::make('receives_sms')
                                        ->label('Receives SMS')
                                        ->options([
                                            1 => 'Yes',
                                            0 => 'No',
                                        ])
                                        ->default(1)
                                        ->required()
                                        ->native(false),
                                    Select::make('can_pick_up')
                                        ->label('Can pick up student')
                                        ->options([
                                            1 => 'Yes',
                                            0 => 'No',
                                        ])
                                        ->default(1)
                                        ->required()
                                        ->native(false),
                                    Select::make('is_primary_contact')
                                        ->label('Primary contact')
                                        ->options([
                                            1 => 'Yes',
                                            0 => 'No',
                                        ])
                                        ->default(0)
                                        ->required()
                                        ->native(false),
                                    Textarea::make('notes')
                                        ->label('Notes')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->addActionLabel('Add another parent / guardian')
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                        ]),
                    Wizard\Step::make('Class Placement')
                        ->description('Place the student in the right session, class, and arm.')
                        ->schema([
                            Repeater::make('enrollment_entries')
                                ->label('Class placements')
                                ->defaultItems(1)
                                ->minItems(1)
                                ->maxItems(2)
                                ->schema([
                                    Select::make('academic_year_id')
                                        ->label('Academic year')
                                        ->options(fn (): array => AcademicYear::query()
                                            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                            ->orderByDesc('starts_on')
                                            ->pluck('name', 'id')
                                            ->all())
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live(),
                                    Select::make('term_id')
                                        ->label('Term')
                                        ->options(fn (Get $get): array => Term::query()
                                            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                            ->when($get('academic_year_id'), fn ($query, $yearId) => $query->where('academic_year_id', $yearId))
                                            ->orderBy('position')
                                            ->pluck('name', 'id')
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
                                        ->required()
                                        ->live(),
                                    Select::make('class_section_id')
                                        ->label('Arm')
                                        ->options(fn (Get $get): array => ClassSection::query()
                                            ->when($get('school_class_id'), fn ($query, $classId) => $query->where('school_class_id', $classId))
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(fn (ClassSection $arm): array => [$arm->getKey() => $arm->name])
                                            ->all())
                                        ->searchable(),
                                    DatePicker::make('enrolled_on')
                                        ->label('Placement date')
                                        ->default(today()),
                                    Select::make('status')
                                        ->required()
                                        ->default('active')
                                        ->options([
                                            'active' => 'Active',
                                            'promoted' => 'Promoted',
                                            'repeated' => 'Repeated',
                                            'withdrawn' => 'Withdrawn',
                                            'completed' => 'Completed',
                                        ]),
                                    Textarea::make('remarks')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->addActionLabel('Add another placement')
                                ->collapsible(),
                        ]),
                    Wizard\Step::make('Health & Contact')
                        ->description('Keep the essential school and health information.')
                        ->schema([
                            TextInput::make('phone')
                                ->label('Student phone')
                                ->tel()
                                ->maxLength(30),
                            TextInput::make('email')
                                ->label('Student email')
                                ->email()
                                ->maxLength(255),
                            TextInput::make('blood_group')
                                ->maxLength(10),
                            TextInput::make('religion')
                                ->maxLength(80),
                            TextInput::make('city')
                                ->maxLength(255),
                            TextInput::make('state')
                                ->maxLength(255),
                            TextInput::make('country')
                                ->default('Nigeria')
                                ->maxLength(255),
                            Textarea::make('address')
                                ->columnSpanFull(),
                            Textarea::make('medical_notes')
                                ->label('Medical notes / allergies')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                    ->skippable()
                    ->columnSpanFull(),
            ]);
    }
}
