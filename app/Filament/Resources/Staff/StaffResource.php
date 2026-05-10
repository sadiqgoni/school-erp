<?php

namespace App\Filament\Resources\Staff;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\Staff\Pages\CreateStaff;
use App\Filament\Resources\Staff\Pages\EditStaff;
use App\Filament\Resources\Staff\Pages\ListStaff;
use App\Filament\Resources\Staff\Pages\ViewStaff;
use App\Filament\Resources\Staff\Schemas\StaffForm;
use App\Filament\Resources\Staff\Tables\StaffTable;
use App\Models\Staff;
use BackedEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StaffResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = Staff::class;

    protected static ?string $navigationLabel = 'Staff Directory';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Staff';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return StaffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(3)
                    ->schema([
                        Section::make('Staff profile')
                            ->schema([
                                ImageEntry::make('photo_path')
                                    ->label('Photo')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->defaultImageUrl(asset('images/branding/school-dice-logo-icon.png'))
                                    ->circular()
                                    ->height(140),
                                TextEntry::make('full_name')
                                    ->label('Name')
                                    ->weight('700')
                                    ->size('lg'),
                                TextEntry::make('staff_number')
                                    ->label('Staff ID')
                                    ->badge()
                                    ->color('primary')
                                    ->copyable(),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'on_leave' => 'info',
                                        'suspended' => 'warning',
                                        'resigned', 'terminated' => 'gray',
                                        default => 'gray',
                                    }),
                            ])
                            ->columnSpan(1),
                        Section::make('Role and school')
                            ->schema([
                                TextEntry::make('school.name')
                                    ->label('School')
                                    ->badge()
                                    ->color('primary'),
                                TextEntry::make('department.name')
                                    ->label('Department / Unit')
                                    ->placeholder('Not set'),
                                TextEntry::make('job_title')
                                    ->label('Role / Position')
                                    ->placeholder('Not set'),
                                TextEntry::make('highest_qualification')
                                    ->label('Qualification')
                                    ->formatStateUsing(fn (?string $state): ?string => $state ? (Staff::QUALIFICATION_OPTIONS[$state] ?? $state) : null)
                                    ->placeholder('Not set'),
                                TextEntry::make('course_specialization')
                                    ->label('Course/Specialization')
                                    ->placeholder('Not set'),
                                TextEntry::make('education_school')
                                    ->label('Education school')
                                    ->placeholder('Not set'),
                                TextEntry::make('trcn_number')
                                    ->label('TRCN / Professional no.')
                                    ->placeholder('Not set'),
                                IconEntry::make('is_teacher')
                                    ->label('Teaching staff')
                                    ->boolean(),
                            ])
                            ->columns(2)
                            ->columnSpan(2),
                    ]),
                Grid::make(3)
                    ->schema([
                        Section::make('Employment')
                            ->schema([
                                TextEntry::make('employment_type')
                                    ->label('Employment type')
                                    ->badge(),
                                TextEntry::make('hire_date')
                                    ->date()
                                    ->placeholder('Not set'),
                                TextEntry::make('basic_salary')
                                    ->label('Basic salary')
                                    ->money('NGN')
                                    ->placeholder('Not set'),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                        Section::make('Contact information')
                            ->schema([
                                TextEntry::make('phone')
                                    ->placeholder('Not set')
                                    ->copyable(),
                                TextEntry::make('email')
                                    ->label('Email address')
                                    ->placeholder('Not set')
                                    ->copyable(),
                                TextEntry::make('gender')
                                    ->placeholder('Not set'),
                                TextEntry::make('date_of_birth')
                                    ->date()
                                    ->placeholder('Not set'),
                                TextEntry::make('address')
                                    ->placeholder('Not set')
                                    ->columnSpanFull(),
                                TextEntry::make('city')
                                    ->placeholder('Not set'),
                                TextEntry::make('state')
                                    ->placeholder('Not set'),
                                TextEntry::make('country')
                                    ->placeholder('Not set'),
                            ])
                            ->columns(2)
                            ->columnSpan(2),
                        Section::make('Bank details')
                            ->schema([
                                TextEntry::make('bank_name')
                                    ->placeholder('Not set'),
                                TextEntry::make('bank_account_name')
                                    ->placeholder('Not set'),
                                TextEntry::make('bank_account_number')
                                    ->placeholder('Not set')
                                    ->copyable(),
                            ])
                            ->columnSpan(1),
                    ]),
                Section::make('Next of kin')
                    ->schema([
                        TextEntry::make('next_of_kin_name')
                            ->label('Name')
                            ->placeholder('Not set'),
                        TextEntry::make('next_of_kin_relation')
                            ->label('Relation')
                            ->formatStateUsing(fn (?string $state): ?string => $state ? ucfirst($state) : null)
                            ->placeholder('Not set'),
                        TextEntry::make('next_of_kin_phone')
                            ->label('Phone number')
                            ->placeholder('Not set')
                            ->copyable(),
                        TextEntry::make('next_of_kin_occupation')
                            ->label('Occupation')
                            ->placeholder('Not set'),
                        TextEntry::make('next_of_kin_address')
                            ->label('Address')
                            ->placeholder('Not set')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Remarks')
                    ->schema([
                        TextEntry::make('notes')
                            ->hiddenLabel()
                            ->placeholder('No remarks recorded.'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'view' => ViewStaff::route('/{record}'),
            'edit' => EditStaff::route('/{record}/edit'),
        ];
    }
}
