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
use App\Support\PayrollCalculator;
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
use Illuminate\Database\Eloquent\Model;

class StaffResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = Staff::class;

    protected static ?string $navigationLabel = 'Staff Directory';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Staff';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'staff_number';

    public static function form(Schema $schema): Schema
    {
        return StaffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffTable::configure($table);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['staff_number', 'first_name', 'middle_name', 'last_name', 'email', 'job_title'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->full_name ?: $record->staff_number;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter([
            'Staff ID' => $record->staff_number,
            'Role' => $record->job_title,
            'Department' => $record->department?->name,
        ]);
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
                                    ->label('Appointment date')
                                    ->date()
                                    ->placeholder('Not set'),
                                TextEntry::make('basic_salary')
                                    ->label('Basic salary')
                                    ->money('NGN')
                                    ->placeholder('Not set'),
                                TextEntry::make('salaryTemplate.name')
                                    ->label('Salary scale')
                                    ->badge()
                                    ->placeholder('Not set'),
                                TextEntry::make('salary_grade_level')
                                    ->label('Grade level')
                                    ->badge()
                                    ->placeholder('Not set'),
                                TextEntry::make('salary_step')
                                    ->label('Step')
                                    ->badge()
                                    ->color('gray')
                                    ->placeholder('Not set'),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                        Section::make('Salary snapshot')
                            ->schema([
                                TextEntry::make('salary_snapshot_basic')
                                    ->label('Basic salary')
                                    ->state(fn (Staff $record): float => PayrollCalculator::snapshotForStaff($record)['basic_salary'])
                                    ->money('NGN')
                                    ->weight('bold'),
                                TextEntry::make('salary_snapshot_allowances')
                                    ->label('Allowances')
                                    ->state(fn (Staff $record): float => PayrollCalculator::snapshotForStaff($record)['allowances_total'])
                                    ->money('NGN')
                                    ->color('success'),
                                TextEntry::make('salary_snapshot_gross')
                                    ->label('Gross pay')
                                    ->state(fn (Staff $record): float => PayrollCalculator::snapshotForStaff($record)['gross_pay'])
                                    ->money('NGN')
                                    ->weight('bold'),
                                TextEntry::make('salary_snapshot_deductions')
                                    ->label('Deductions')
                                    ->state(fn (Staff $record): float => PayrollCalculator::snapshotForStaff($record)['deductions_total'])
                                    ->money('NGN')
                                    ->color('danger'),
                                TextEntry::make('salary_snapshot_net')
                                    ->label('Net pay')
                                    ->state(fn (Staff $record): float => PayrollCalculator::snapshotForStaff($record)['net_pay'])
                                    ->money('NGN')
                                    ->weight('bold')
                                    ->color('success'),
                                TextEntry::make('salary_snapshot_items')
                                    ->label('Items')
                                    ->state(function (Staff $record): array {
                                        $snapshot = PayrollCalculator::snapshotForStaff($record);
                                        $allowances = collect($snapshot['allowances'])->map(fn (array $item): string => $item['name'].' +NGN '.number_format((float) $item['amount'], 2));
                                        $deductions = collect($snapshot['deductions'])->map(fn (array $item): string => $item['name'].' -NGN '.number_format((float) $item['amount'], 2));

                                        return $allowances->merge($deductions)->values()->all() ?: ['No allowance or deduction items are active for this grade.'];
                                    })
                                    ->listWithLineBreaks()
                                    ->columnSpanFull(),
                            ])
                            ->columns(5)
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
