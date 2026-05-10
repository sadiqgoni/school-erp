<?php

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\Pages\ViewStudent;
use App\Filament\Resources\Students\Schemas\StudentForm;
use App\Filament\Resources\Students\Tables\StudentsTable;
use App\Models\Student;
use BackedEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StudentResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = Student::class;

    protected static ?string $navigationLabel = 'Students';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|\UnitEnum|null $navigationGroup = 'Students';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return StudentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(3)
                    ->schema([
                        Section::make('Admission profile')
                            ->schema([
                                ImageEntry::make('photo_path')
                                    ->label('Photo')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->defaultImageUrl('https://ui-avatars.com/api/?name=Student&background=E2E8F0&color=334155')
                                    ->circular()
                                    ->height(140),
                                TextEntry::make('full_name')
                                    ->label('Student')
                                    ->weight('700')
                                    ->size('lg'),
                                TextEntry::make('admission_number')
                                    ->label('Admission number')
                                    ->badge()
                                    ->color('primary')
                                    ->copyable(),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'suspended' => 'warning',
                                        'graduated' => 'info',
                                        'transferred', 'inactive' => 'gray',
                                        default => 'gray',
                                    }),
                            ])
                            ->columnSpan(1),
                        Section::make('Student details')
                            ->schema([
                                TextEntry::make('school.name')
                                    ->label('School')
                                    ->badge()
                                    ->color('primary'),
                                TextEntry::make('gender')
                                    ->badge()
                                    ->placeholder('Not set'),
                                TextEntry::make('date_of_birth')
                                    ->date()
                                    ->placeholder('Not set'),
                                TextEntry::make('admitted_on')
                                    ->label('Admission date')
                                    ->date()
                                    ->placeholder('Not set'),
                                TextEntry::make('previous_school')
                                    ->placeholder('Not set'),
                                TextEntry::make('religion')
                                    ->placeholder('Not set'),
                            ])
                            ->columns(2)
                            ->columnSpan(2),
                    ]),
                Grid::make(3)
                    ->schema([
                        Section::make('Contact and address')
                            ->schema([
                                TextEntry::make('phone')
                                    ->label('Student phone')
                                    ->placeholder('Not set')
                                    ->copyable(),
                                TextEntry::make('email')
                                    ->label('Student email')
                                    ->placeholder('Not set')
                                    ->copyable(),
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
                        Section::make('Health')
                            ->schema([
                                TextEntry::make('blood_group')
                                    ->label('Blood group')
                                    ->placeholder('Not set'),
                                TextEntry::make('medical_notes')
                                    ->label('Medical notes / allergies')
                                    ->placeholder('No medical notes recorded.'),
                            ])
                            ->columnSpan(1),
                    ]),
                Section::make('Class placement history')
                    ->schema([
                        RepeatableEntry::make('enrollments')
                            ->hiddenLabel()
                            ->placeholder('No class placement has been recorded.')
                            ->schema([
                                TextEntry::make('academicYear.name')
                                    ->label('Academic year')
                                    ->badge()
                                    ->color('primary')
                                    ->placeholder('Not set'),
                                TextEntry::make('term.name')
                                    ->placeholder('Not set'),
                                TextEntry::make('schoolClass.name')
                                    ->label('Class')
                                    ->placeholder('Not set'),
                                TextEntry::make('classSection.name')
                                    ->label('Arm')
                                    ->placeholder('Not set'),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('enrolled_on')
                                    ->label('Placement date')
                                    ->date()
                                    ->placeholder('Not set'),
                                TextEntry::make('remarks')
                                    ->placeholder('No remarks')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),
                Section::make('Parents / Guardians')
                    ->schema([
                        RepeatableEntry::make('guardianLinks')
                            ->hiddenLabel()
                            ->placeholder('No parent or guardian has been recorded.')
                            ->schema([
                                TextEntry::make('guardian.name')
                                    ->label('Name')
                                    ->weight('600')
                                    ->placeholder('Not set'),
                                TextEntry::make('relationship')
                                    ->badge(),
                                TextEntry::make('guardian.phone')
                                    ->label('Phone')
                                    ->copyable()
                                    ->placeholder('Not set'),
                                TextEntry::make('guardian.alternate_phone')
                                    ->label('Alternate phone')
                                    ->copyable()
                                    ->placeholder('Not set'),
                                TextEntry::make('guardian.email')
                                    ->label('Email address')
                                    ->copyable()
                                    ->placeholder('Not set'),
                                TextEntry::make('guardian.occupation')
                                    ->placeholder('Not set'),
                                IconEntry::make('is_primary_contact')
                                    ->label('Primary contact')
                                    ->boolean(),
                                IconEntry::make('can_pick_up')
                                    ->label('Can pick up')
                                    ->boolean(),
                                IconEntry::make('receives_sms')
                                    ->label('Receives SMS')
                                    ->boolean(),
                                TextEntry::make('guardian.address')
                                    ->label('Address')
                                    ->placeholder('Not set')
                                    ->columnSpanFull(),
                                TextEntry::make('notes')
                                    ->placeholder('No notes')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
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
            'index' => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'view' => ViewStudent::route('/{record}'),
            'edit' => EditStudent::route('/{record}/edit'),
        ];
    }
}
