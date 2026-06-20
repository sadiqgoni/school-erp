<?php

namespace App\Filament\Resources\PayrollItemTypes;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\PayrollItemTypes\Pages\CreatePayrollItemType;
use App\Filament\Resources\PayrollItemTypes\Pages\EditPayrollItemType;
use App\Filament\Resources\PayrollItemTypes\Pages\ListPayrollItemTypes;
use App\Filament\Support\SchoolSelect;
use App\Models\Concerns\CalculatesSalaryItemAmount;
use App\Models\LedgerAccount;
use App\Models\PayrollItemType;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayrollItemTypeResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = PayrollItemType::class;

    protected static ?string $navigationLabel = 'Payroll Elements';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|\UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?int $navigationSort = 46;

    protected static ?string $modelLabel = 'Payroll Element';

    protected static ?string $pluralModelLabel = 'Payroll Elements';

    protected static function calculationTypeOptions(): array
    {
        return [
            'fixed_amount' => 'Fixed Amount',
            'percentage_of_gross' => 'Percentage of Gross',
            'percentage_of_item' => 'Percentage of Item',
            'grade_based' => 'Grade-Based',
            'salary_structure' => 'Salary Structure',
            'percentage_of_gross_with_exclusions' => 'Percentage with Exclusions',
            'sum_of_items' => 'Sum of Items',
            'percentage_of_sum' => 'Percentage of Sum',
            'anniversary_based' => 'Anniversary Month-Based',
            'leave_grant' => 'Leave Grant (20% Annual)',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Wizard\Step::make('Basic Information')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->description('Enter the name, account, and type')
                    ->schema([
                        Section::make('Payroll Element Details')
                            ->schema([
                                SchoolSelect::make(),
                                TextInput::make('name')
                                    ->label('Element name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Housing Allowance, PAYE Tax, Pension Contribution')
                                    ->columnSpanFull(),
                                Select::make('ledger_account_id')
                                    ->label('Chart of account')
                                    ->options(fn (): array => LedgerAccount::query()
                                        ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                        ->where('is_active', true)
                                        ->orderBy('code')
                                        ->get()
                                        ->mapWithKeys(fn (LedgerAccount $account): array => [$account->getKey() => "{$account->code} - {$account->name}"])
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpanFull(),
                                Grid::make(2)
                                    ->schema([
                                        Select::make('type')
                                            ->label('Element type')
                                            ->required()
                                            ->native(false)
                                            ->options([
                                                PayrollItemType::TYPE_ALLOWANCE => 'Earning',
                                                PayrollItemType::TYPE_DEDUCTION => 'Deduction',
                                            ]),
                                        Toggle::make('is_active')
                                            ->label('Active')
                                            ->default(true),
                                    ]),
                            ])
                            ->columns(2),
                    ]),
                Wizard\Step::make('Calculation Setup')
                    ->icon(Heroicon::OutlinedCalculator)
                    ->description('Set how this payroll element should calculate')
                    ->schema([
                        Section::make('Calculation Method')
                            ->schema([
                                Select::make('calculation_type')
                                    ->label('Calculation Method')
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->options(static::calculationTypeOptions())
                                    ->default('fixed_amount')
                                    ->columnSpanFull(),
                            ]),
                        Section::make('Fixed Amount')
                            ->schema([
                                TextInput::make('calculation_details.value')
                                    ->label('Amount')
                                    ->numeric()
                                    ->required()
                                    ->prefix('NGN')
                                    ->placeholder('25000'),
                            ])
                            ->visible(fn (callable $get): bool => $get('calculation_type') === 'fixed_amount'),
                        Section::make('Percentage of Gross')
                            ->schema([
                                TextInput::make('calculation_details.value')
                                    ->label('Percentage')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->placeholder('10'),
                            ])
                            ->visible(fn (callable $get): bool => $get('calculation_type') === 'percentage_of_gross'),
                        Section::make('Percentage of Another Item')
                            ->schema([
                                Select::make('calculation_details.base_item')
                                    ->label('Base Item')
                                    ->options(function (): array {
                                        return ['basic_salary' => 'Basic Salary'] + PayrollItemType::query()
                                            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->all();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('calculation_details.value')
                                    ->label('Percentage')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->placeholder('15'),
                            ])
                            ->visible(fn (callable $get): bool => $get('calculation_type') === 'percentage_of_item')
                            ->columns(2),
                        Section::make('Grade-Based Amount')
                            ->schema([
                                KeyValue::make('calculation_details.grade_rules')
                                    ->label('Grade-Based Rules')
                                    ->keyLabel('Grade/Range')
                                    ->valueLabel('Amount')
                                    ->keyPlaceholder('1-5 or 10+')
                                    ->valuePlaceholder('25000')
                                    ->reorderable()
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn (callable $get): bool => $get('calculation_type') === 'grade_based'),
                        Section::make('Salary Structure')
                            ->schema([
                                Toggle::make('calculation_details.auto_fetch')
                                    ->label('Automatically fetch from salary table')
                                    ->default(true),
                            ])
                            ->visible(fn (callable $get): bool => $get('calculation_type') === 'salary_structure'),
                        Section::make('Percentage with Exclusions')
                            ->schema([
                                TextInput::make('calculation_details.value')
                                    ->label('Percentage')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->placeholder('4'),
                                CheckboxList::make('calculation_details.excluded_items')
                                    ->label('Items to Exclude')
                                    ->options(fn (): array => PayrollItemType::query()
                                        ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                        ->where('type', PayrollItemType::TYPE_ALLOWANCE)
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->columns(2),
                            ])
                            ->visible(fn (callable $get): bool => $get('calculation_type') === 'percentage_of_gross_with_exclusions'),
                        Section::make('Sum of Items')
                            ->schema([
                                CheckboxList::make('calculation_details.items_to_sum')
                                    ->label('Items to Sum')
                                    ->options(fn (): array => PayrollItemType::query()
                                        ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->columns(2)
                                    ->required(),
                            ])
                            ->visible(fn (callable $get): bool => $get('calculation_type') === 'sum_of_items'),
                        Section::make('Percentage of Sum')
                            ->schema([
                                CheckboxList::make('calculation_details.items_to_sum')
                                    ->label('Items to Sum')
                                    ->options(fn (): array => PayrollItemType::query()
                                        ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->columns(2)
                                    ->required(),
                                TextInput::make('calculation_details.percentage')
                                    ->label('Percentage')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->placeholder('8'),
                            ])
                            ->visible(fn (callable $get): bool => $get('calculation_type') === 'percentage_of_sum'),
                        Section::make('Anniversary Month-Based')
                            ->schema([
                                Toggle::make('calculation_details.anniversary_only')
                                    ->label('Show only in anniversary month')
                                    ->default(true)
                                    ->required(),
                                Select::make('calculation_details.amount_method')
                                    ->label('Amount Calculation Method')
                                    ->options([
                                        'fixed' => 'Fixed Amount',
                                        'grade_based' => 'Grade-Based Amount',
                                        'percentage_of_basic' => 'Percentage of Basic Salary',
                                    ])
                                    ->native(false)
                                    ->default('grade_based')
                                    ->live()
                                    ->required(),
                                TextInput::make('calculation_details.fixed_amount')
                                    ->label('Fixed Amount')
                                    ->numeric()
                                    ->prefix('NGN')
                                    ->required()
                                    ->visible(fn (callable $get): bool => $get('calculation_details.amount_method') === 'fixed'),
                                KeyValue::make('calculation_details.grade_rules')
                                    ->label('Grade-Based Rules')
                                    ->keyLabel('Grade/Range')
                                    ->valueLabel('Amount')
                                    ->keyPlaceholder('1-5 or 10+')
                                    ->valuePlaceholder('25000')
                                    ->reorderable()
                                    ->required()
                                    ->visible(fn (callable $get): bool => $get('calculation_details.amount_method') === 'grade_based')
                                    ->columnSpanFull(),
                                TextInput::make('calculation_details.percentage_value')
                                    ->label('Percentage of Basic Salary')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->visible(fn (callable $get): bool => $get('calculation_details.amount_method') === 'percentage_of_basic'),
                            ])
                            ->visible(fn (callable $get): bool => $get('calculation_type') === 'anniversary_based'),
                        Section::make('Leave Grant')
                            ->schema([
                                Toggle::make('calculation_details.anniversary_only')
                                    ->label('Show only in anniversary month')
                                    ->default(true)
                                    ->required(),
                                Placeholder::make('leave_grant_info')
                                    ->label('Leave Grant Calculation')
                                    ->content('Leave Grant is calculated as 20% of annual basic salary.'),
                            ])
                            ->visible(fn (callable $get): bool => $get('calculation_type') === 'leave_grant'),
                        Section::make('Notes')
                            ->schema([
                                Textarea::make('notes')
                                    ->rows(4)
                                    ->placeholder('Optional internal note for the finance team.')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === PayrollItemType::TYPE_ALLOWANCE ? 'Earning' : 'Deduction')
                    ->color(fn (string $state): string => $state === PayrollItemType::TYPE_ALLOWANCE ? 'success' : 'danger'),
                TextColumn::make('name')
                    ->label('Element Name')
                    ->weight('semibold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (PayrollItemType $record): string => $record->ledgerAccount ? "{$record->ledgerAccount->code} - {$record->ledgerAccount->name}" : 'No chart of account linked'),
                TextColumn::make('calculation_type')
                    ->label('Logic')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CalculatesSalaryItemAmount::calculationOptions()[$state] ?? $state)
                    ->color('gray'),
                TextColumn::make('value')
                    ->label('Value')
                    ->formatStateUsing(function ($state, PayrollItemType $record): string {
                        if (in_array($record->calculation_type, ['percentage_of_gross', 'percentage_of_item', 'percentage_of_sum', 'percentage_of_gross_with_exclusions'], true)) {
                            return number_format((float) $state, 2).'%';
                        }

                        if ($record->calculation_type === 'salary_structure') {
                            return 'Salary table';
                        }

                        return 'NGN '.number_format((float) $state, 2);
                    })
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        PayrollItemType::TYPE_ALLOWANCE => 'Earnings',
                        PayrollItemType::TYPE_DEDUCTION => 'Deductions',
                    ]),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollItemTypes::route('/'),
            'create' => CreatePayrollItemType::route('/create'),
            'edit' => EditPayrollItemType::route('/{record}/edit'),
        ];
    }
}
