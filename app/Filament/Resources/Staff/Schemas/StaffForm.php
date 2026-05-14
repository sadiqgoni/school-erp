<?php

namespace App\Filament\Resources\Staff\Schemas;

use App\Filament\Support\SchoolSelect;
use App\Models\Staff;
use App\Models\StaffRole;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;

class StaffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Wizard\Step::make('Staff Setup')
                        ->description('Start with school unit and role/position.')
                        ->schema([
                            SchoolSelect::make(),
                            Select::make('staff_type')
                                ->label('Staff type')
                                ->required()
                                ->default(Staff::TYPE_TEACHING)
                                ->options([
                                    Staff::TYPE_TEACHING => 'Teaching staff',
                                    Staff::TYPE_NON_TEACHING => 'Non-teaching staff',
                                ])
                                ->native(false),
                            Select::make('department_id')
                                ->label('Department / Unit')
                                ->relationship('department', 'name')
                                ->searchable()
                                ->preload()
                                ->helperText('Examples: Academics, Admin Office, Accounts, ICT, Transport, Security.'),
                            Select::make('job_title')
                                ->label('Role / Position')
                                ->options(
                                    StaffRole::query()
                                        ->when(
                                            Filament::getTenant(),
                                            fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()),
                                        )
                                        ->orderBy('name')
                                        ->pluck('name', 'name')
                                        ->all(),
                                )
                                ->searchable()
                                ->preload()
                                ->helperText('Pick from the staff roles you already created.'),
                            Select::make('status')
                                ->required()
                                ->default('active')
                                ->options([
                                    'active' => 'Active',
                                    'on_leave' => 'On leave',
                                    'suspended' => 'Suspended',
                                    'resigned' => 'Resigned',
                                    'terminated' => 'Terminated',
                                ]),
                        ])
                        ->columns(2),
                    Wizard\Step::make('Personal Details')
                        ->description('Capture the staff bio-data and school ID.')
                        ->schema([
                            FileUpload::make('photo_path')
                                ->label('Staff photo')
                                ->image()
                                ->avatar()
                                ->disk('public')
                                ->directory('staff-photos')
                                ->visibility('public')
                                ->imageEditor()
                                ->columnSpanFull(),
                            TextInput::make('staff_number')
                                ->label('Staff ID')
                                ->required()
                                ->maxLength(50)
                                ->unique(
                                    ignorable: fn ($record) => $record,
                                    modifyRuleUsing: fn ($rule) => $rule->where('school_id', Filament::getTenant()?->getKey()),
                                ),
                            TextInput::make('first_name')
                                ->required()
                                ->maxLength(80),
                            TextInput::make('middle_name')
                                ->maxLength(80),
                            TextInput::make('last_name')
                                ->required()
                                ->maxLength(80),
                            Select::make('gender')
                                ->options([
                                    'male' => 'Male',
                                    'female' => 'Female',
                                ]),
                            DatePicker::make('date_of_birth'),
                            TextInput::make('trcn_number')
                                ->label('TRCN / Professional no.')
                                ->maxLength(50),
                        ])
                        ->columns(2),
                    Wizard\Step::make('Education Information')
                        ->description('Record academic background and area of specialization.')
                        ->schema([
                            Select::make('highest_qualification')
                                ->label('Highest Qualification')
                                ->required()
                                ->placeholder('Choose...')
                                ->options(Staff::QUALIFICATION_OPTIONS)
                                ->native(false),
                            TextInput::make('course_specialization')
                                ->label('Course/Specialization')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('education_school')
                                ->label('School')
                                ->maxLength(255),
                        ])
                        ->columns(2),
                    Wizard\Step::make('Employment')
                        ->description('Save employment arrangement and pay details.')
                        ->schema([
                            Select::make('employment_type')
                                ->required()
                                ->default('full_time')
                                ->options([
                                    'full_time' => 'Full time',
                                    'part_time' => 'Part time',
                                    'contract' => 'Contract',
                                    'temporary' => 'Temporary',
                                ]),
                            DatePicker::make('hire_date'),
                            TextInput::make('basic_salary')
                                ->numeric()
                                ->prefix('NGN'),
                            TextInput::make('bank_name')
                                ->maxLength(255),
                            TextInput::make('bank_account_name')
                                ->maxLength(255),
                            TextInput::make('bank_account_number')
                                ->maxLength(30),
                        ])
                        ->columns(2),
                    Wizard\Step::make('Next Of Kin Information')
                        ->description('Save emergency contact details for this staff member.')
                        ->schema([
                            TextInput::make('next_of_kin_name')
                                ->label('Name')
                                ->required()
                                ->maxLength(255),
                            Select::make('next_of_kin_relation')
                                ->label('Relation')
                                ->required()
                                ->placeholder('Choose...')
                                ->options([
                                    'spouse' => 'Spouse',
                                    'father' => 'Father',
                                    'mother' => 'Mother',
                                    'sibling' => 'Sibling',
                                    'child' => 'Child',
                                    'uncle' => 'Uncle',
                                    'aunt' => 'Aunt',
                                    'friend' => 'Friend',
                                    'guardian' => 'Guardian',
                                    'other' => 'Other',
                                ])
                                ->native(false),
                            TextInput::make('next_of_kin_phone')
                                ->label('Phone Number')
                                ->tel()
                                ->required()
                                ->maxLength(30),
                            TextInput::make('next_of_kin_occupation')
                                ->label('Occupation')
                                ->required()
                                ->maxLength(255),
                            Textarea::make('next_of_kin_address')
                                ->label('Address')
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Wizard\Step::make('Contact')
                        ->description('Finish with contact and address details.')
                        ->schema([
                            TextInput::make('phone')
                                ->tel()
                                ->maxLength(30),
                            TextInput::make('email')
                                ->email()
                                ->maxLength(255),
                            TextInput::make('city')
                                ->maxLength(255),
                            TextInput::make('state')
                                ->maxLength(255),
                            TextInput::make('country')
                                ->default('Nigeria')
                                ->maxLength(255),
                            Textarea::make('address')
                                ->columnSpanFull(),
                            Textarea::make('notes')
                                ->label('Remarks')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Wizard\Step::make('Portal Access')
                        ->description('Create a login only when this staff member needs portal access.')
                        ->schema([
                            Toggle::make('create_login_account')
                                ->label('Create login account')
                                ->dehydrated(false)
                                ->live(),
                            TextInput::make('login_email')
                                ->label('Login email')
                                ->email()
                                ->maxLength(255)
                                ->dehydrated(false)
                                ->visible(fn ($get): bool => (bool) $get('create_login_account'))
                                ->required(fn ($get): bool => (bool) $get('create_login_account'))
                                ->helperText('Use the email this staff member will use to sign in.'),
                            TextInput::make('temporary_password')
                                ->label('Temporary password')
                                ->password()
                                ->revealable()
                                ->minLength(8)
                                ->dehydrated(false)
                                ->visible(fn ($get): bool => (bool) $get('create_login_account'))
                                ->required(fn ($get): bool => (bool) $get('create_login_account'))
                                ->helperText('The staff member should change this after first login.'),
                        ])
                        ->columns(2),
                ])
                    ->skippable()
                    ->columnSpanFull(),
            ]);
    }
}
