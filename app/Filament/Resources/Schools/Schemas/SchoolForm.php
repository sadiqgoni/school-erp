<?php

namespace App\Filament\Resources\Schools\Schemas;

use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SchoolForm
{
    public static function configure(Schema $schema, bool $includeAdminAccount = false, bool $isTenantProfile = false): Schema
    {
        $components = [
            Section::make('School details')
                ->description($isTenantProfile ? 'Update your school identity and contact details.' : 'Set up the school identity, contact details, and portal address.')
                ->schema([
                    FileUpload::make('logo_path')
                        ->label('School logo')
                        ->image()
                        ->disk('public')
                        ->directory('school-logos')
                        ->visibility('public')
                        ->imageEditor()
                        ->columnSpanFull()
                        ->helperText('Upload a clear school logo for display in the portal.'),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Greenfield International School'),
                    TextInput::make('code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(30)
                        ->placeholder('GIS')
                        ->helperText('Short unique code, for example GGS, KCS, or DIS.'),
                    TextInput::make('slug')
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->placeholder('greenfield-international-school')
                        ->helperText('Used in the school portal URL.'),
                    TextInput::make('email')
                        ->email()
                        ->maxLength(255)
                        ->placeholder('info@gmail.com'),
                    TextInput::make('phone')
                        ->tel()
                        ->maxLength(30)
                        ->placeholder('+2348000000000'),
                    TextInput::make('address')
                        ->columnSpanFull()
                        ->placeholder('School address'),
                    TextInput::make('city')
                        ->maxLength(255)
                        ->placeholder('Abuja'),
                    TextInput::make('state')
                        ->maxLength(255)
                        ->placeholder('FCT'),
                    TextInput::make('country')
                        ->required()
                        ->maxLength(255)
                        ->default('Nigeria'),
                ])
                ->columns(3)->collapsible()->columnSpanFull(),
            Section::make('Portal settings')
                ->schema([
                    CheckboxList::make('sections')
                        ->label('School sections')
                        ->options(School::DIVISIONS)
                        ->default(function (?School $record): array {
                            if (! $record) {
                                return array_keys(School::DIVISIONS);
                            }

                            $rootSchoolId = $record->parent_school_id ?: $record->getKey();

                            $sections = School::query()
                                ->withoutGlobalScope('school-panel-current-tenant')
                                ->where('parent_school_id', $rootSchoolId)
                                ->where('is_active', true)
                                ->pluck('division')
                                ->filter()
                                ->values()
                                ->all();

                            return $sections !== [] ? $sections : array_keys(School::DIVISIONS);
                        })
                        ->required()
                        ->columns(3)
                        ->helperText($isTenantProfile
                            ? 'Choose the active school section workspaces for this school.'
                            : 'Choose the section workspaces this school should have in the portal.')
                        ->visible(fn (string $operation): bool => $operation === 'create' || ($isTenantProfile && (bool) Filament::auth()->user()?->isSuperAdmin()))
                        ->columnSpanFull(),
                    Select::make('subscription_plan')
                        ->required()
                        ->options([
                            'trial' => 'Free Trial',
                            'basic_ngn' => 'Basic',
                            'standard_ngn' => 'Standard',
                            'premium_ngn' => 'Premium',
                        ])
                        ->default('trial'),
                    DateTimePicker::make('subscription_expires_at'),
                    TextInput::make('student_limit')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->default(1000)
                        ->suffix('students'),
                    Toggle::make('is_active')
                        ->default(true)
                        ->inline(false),
                ])
                ->columns(2),
            Section::make('Fee policy')
                ->schema([
                    Toggle::make('withhold_results_for_debtors')
                        ->label('Withhold a term\'s result until that term\'s fees are cleared')
                        ->helperText('When on, a parent cannot view or download a published report card for a term while there is an outstanding balance for that same term. Staff and admins can always see it. Off by default.')
                        ->default(false)
                        ->inline(false),
                ])
                ->columns(1),
            Section::make('Enabled modules')
                ->description('Choose the areas this school should see in its portal first.')
                ->schema([
                    CheckboxList::make('enabled_modules')
                        ->columns(3)
                        ->options([
                            'students' => 'Students',
                            'staff' => 'Staff',
                            'attendance' => 'Attendance',
                            'fees' => 'Fees',
                            'exams' => 'Exams',
                            'reports' => 'Reports',
                            'library' => 'Library',
                            'transport' => 'Transport',
                            'hostel' => 'Hostel',
                            'communications' => 'Communications',
                        ])
                        ->default([
                            'students',
                            'staff',
                            'attendance',
                            'fees',
                            'exams',
                            'reports',
                            'communications',
                        ]),
                ])
                ->compact(),
        ];

        if ($includeAdminAccount) {
            $components[] = Section::make('School admin')
                ->description('These are the first login details the school will use in the portal.')
                ->visible(fn (string $operation): bool => $operation === 'create')
                ->schema([
                    TextInput::make('admin_name')
                        ->label('Full name')
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->maxLength(255)
                        ->placeholder('Amina Yusuf'),
                    Select::make('admin_role')
                        ->label('Role')
                        ->options([
                            User::SCHOOL_ROLE_ADMIN => 'School Admin',
                        ])
                        ->default(User::SCHOOL_ROLE_ADMIN)
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->selectablePlaceholder(false),
                    TextInput::make('admin_email')
                        ->label('Email address')
                        ->email()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->unique(table: 'users', column: 'email')
                        ->maxLength(255)
                        ->placeholder('admin@gmail.com')
                        ->helperText('This email will be used to log into the school portal.'),
                    TextInput::make('admin_password')
                        ->label('Temporary password')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->minLength(8)
                        ->placeholder('Minimum 8 characters')
                        ->helperText('The school can change this after the first login.'),
                ])
                ->columns(2)->columnSpanFull();
        }

        return $schema
            ->components($components);
    }
}
