<?php
namespace App\Filament\Finance\Resources;
use App\Filament\Finance\Resources\PayrollItemTypeResource\Pages;
use App\Filament\Finance\Resources\PayeeResource\RelationManagers;
use App\Models\Finance\PayrollItemType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select as FormsSelect;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\ToggleButtons;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PayrollItemTypeResource extends Resource
{
    protected static ?string $model = PayrollItemType::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Payroll Management';

    protected static ?string $modelLabel = 'Salary Structure';

    protected static ?string $pluralModelLabel = 'Salary Structure';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Basic Information')
                        ->icon('heroicon-o-information-circle')
                        ->description('Enter the name and type')
                        ->schema([
                            Section::make('Payroll Item Details')
                                ->description('Set the basic information for this payroll item')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Item Name')
                                        ->placeholder('e.g., Basic Salary, Rent Allowance, PAYE')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    FormsSelect::make('code')
                                        ->label('Chart of Account Code')
                                        ->placeholder('Select corresponding chart of account')
                                        ->options(function () {
                                            return \App\Models\Finance\ChartOfAccount::where('chart_account_category', 'EXPENSES')
                                                ->orderBy('code')
                                                ->get()
                                                ->mapWithKeys(function ($account) {
                                                    return [$account->code => $account->code . ' - ' . $account->name];
                                                });
                                        })
                                        ->searchable()
                                        ->helperText('Link this payroll item to its corresponding chart of account for financial reporting')
                                        ->columnSpanFull(),

                                    Grid::make(2)
                                        ->schema([
                                            FormsSelect::make('type')
                                                ->label('Item Type')
                                                ->helperText('Is this an earning (payment to employee) or deduction (taken from employee)?')
                                                ->options([
                                                    'earning' => 'Earning',
                                                    'deduction' => 'Deduction',
                                                    'employer_contribution' => 'Employer Contribution'

                                                ])
                                                ->required(),

                                            Toggle::make('is_active')
                                                ->label('Active')
                                                ->helperText('Inactive items will not appear in payroll calculations')
                                                ->default(true)
                                                ->required(),
                                        ]),
                                    Placeholder::make('basic_help')
                                        ->content('After creating basic information, you will configure how this item is calculated in the next step.')
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull(),
                        ]),

                    Wizard\Step::make('Calculation Method')
                        ->icon('heroicon-o-calculator')
                        ->description('Define how this item is calculated')
                        ->schema([
                            Section::make('How is this calculated?')
                                ->description('Choose a calculation method below that best matches how this item should be calculated. You\'ll provide more details in the next step.')
                                ->schema([
                                    FormsSelect::make('calculation_type')
                                        ->label('Calculation Method')
                                        ->helperText('Select the method that best describes how this item is calculated')
                                        ->options([
                                            'fixed_amount' => 'Fixed Amount (same for everyone)',
                                            'percentage_of_gross' => 'Percentage of Gross Salary (e.g., 10% of gross)',
                                            'percentage_of_item' => 'Percentage of Another Item (e.g., 50% of Basic Salary)',
                                            'grade_based' => 'Grade-Based Amount (different amounts by grade level)',
                                            'salary_structure' => 'Based on Salary Structure (from the official table)',
                                            'percentage_of_gross_with_exclusions' => 'Percentage of Gross with Exclusions',
                                            'sum_of_items' => 'Sum of Multiple Items',
                                            'percentage_of_sum' => 'Percentage of Sum of Items',
                                            'anniversary_based' => 'Anniversary Month-Based Item',
                                            'leave_grant' => 'Leave Grant (20% of Annual Basic Salary)',
                                        ])
                                        ->columnSpanFull()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $method = match ($state) {
                                                'fixed_amount' => 'fixed',
                                                'grade_based' => 'grade_based',
                                                'percentage_of_item', 'percentage_of_gross', 'percentage_of_gross_with_exclusions' => 'percentage',
                                                'sum_of_items' => 'composite',
                                                'percentage_of_sum' => 'composite_percentage',
                                                'salary_structure' => 'salary_structure',
                                                'anniversary_based' => 'anniversary_based',
                                                'leave_grant' => 'leave_grant',
                                                default => 'fixed',
                                            };
                                            $set('calculation_details.method', $method);

                                            // Set default anniversary_only to true for anniversary-based items
                                            if ($state === 'anniversary_based' || $state === 'leave_grant') {
                                                $set('calculation_details.anniversary_only', true);
                                            }
                                        })
                                        ->required(),

                                    Card::make()
                                        ->schema([
                                            Placeholder::make('fixed_description')
                                                ->label('Fixed Amount')
                                                ->content('A consistent amount that applies to all employees regardless of their grade or salary. For example, a transportation allowance of ₦20,000 per month for everyone.')
                                                ->columnSpanFull(),
                                        ])
                                        ->columnSpanFull()
                                        ->visible(fn(callable $get) => $get('calculation_type') === 'fixed_amount'),

                                    Card::make()
                                        ->schema([
                                            Placeholder::make('percentage_gross_description')
                                                ->label('Percentage of Gross Salary')
                                                ->content('Calculates as a percentage of the employee\'s total gross salary. For example, PAYE might be 5% of gross salary.')
                                                ->columnSpanFull(),
                                        ])
                                        ->columnSpanFull()
                                        ->visible(fn(callable $get) => $get('calculation_type') === 'percentage_of_gross'),

                                    Card::make()
                                        ->schema([
                                            Placeholder::make('percentage_item_description')
                                                ->label('Percentage of Another Item')
                                                ->content('Calculates as a percentage of another payroll item. For example, Rent Allowance might be 50% of Basic Salary.')
                                                ->columnSpanFull(),
                                        ])
                                        ->columnSpanFull()
                                        ->visible(fn(callable $get) => $get('calculation_type') === 'percentage_of_item'),

                                    Card::make()
                                        ->schema([
                                            Placeholder::make('grade_based_description')
                                                ->label('Grade-Based Amount')
                                                ->content('Different fixed amounts based on employee grade level. For example, Transport Allowance might be ₦15,000 for GL 1-5, ₦20,000 for GL 6-10, and ₦30,000 for GL 11+.')
                                                ->columnSpanFull(),
                                        ])
                                        ->columnSpanFull()
                                        ->visible(fn(callable $get) => $get('calculation_type') === 'grade_based'),

                                    Card::make()
                                        ->schema([
                                            Placeholder::make('salary_structure_description')
                                                ->label('Based on Salary Structure')
                                                ->content('Pulls values directly from the official salary structure based on employee\'s grade and step. Typically used for Basic Salary.')
                                                ->columnSpanFull(),
                                        ])
                                        ->columnSpanFull()
                                        ->visible(fn(callable $get) => $get('calculation_type') === 'salary_structure'),

                                    Card::make()
                                        ->schema([
                                            Placeholder::make('anniversary_based_description')
                                                ->label('Anniversary Month-Based Item')
                                                ->content('This item will only appear in the staff\'s anniversary month. You can define grade-based amounts or other calculation methods that will only be applied during the anniversary month.')
                                                ->columnSpanFull(),
                                        ])
                                        ->columnSpanFull()
                                        ->visible(fn(callable $get) => $get('calculation_type') === 'anniversary_based'),

                                    Card::make()
                                        ->schema([
                                            Placeholder::make('leave_grant_description')
                                                ->label('Leave Grant')
                                                ->content('Automatically calculates 20% of annual basic salary. This appears only during the staff\'s anniversary month as specified in their profile.')
                                                ->columnSpanFull(),
                                        ])
                                        ->columnSpanFull()
                                        ->visible(fn(callable $get) => $get('calculation_type') === 'leave_grant'),
                                ])
                                ->columnSpanFull(),
                        ]),

                    Wizard\Step::make('Calculation Details')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->description('Configure the specific values')
                        ->schema([
                            // FIXED AMOUNT
                            Section::make('Fixed Amount')
                                ->schema([
                                    TextInput::make('calculation_details.value')
                                        ->label('Amount (₦)')
                                        ->numeric()
                                        ->required()
                                        ->prefix('₦')
                                        ->placeholder('e.g., 25000')
                                        ->helperText('Enter the fixed amount that applies to all employees'),

                                    Placeholder::make('fixed_example')
                                        ->label('Example')
                                        ->content(function (callable $get) {
                                            $value = $get('calculation_details.value') ?: 0;
                                            return "Every employee will receive <strong>₦" . number_format($value, 2) . "</strong> for this item regardless of their grade or step.";
                                        }),
                                ])
                                ->columnSpanFull()
                                ->visible(fn(callable $get) => $get('calculation_type') === 'fixed_amount')
                                ->columns(1),

                            // PERCENTAGE OF GROSS
                            Section::make('Percentage of Gross Salary')
                                ->schema([
                                    TextInput::make('calculation_details.value')
                                        ->label('Percentage')
                                        ->numeric()
                                        ->suffix('%')
                                        ->required()
                                        ->placeholder('e.g., 10')
                                        ->helperText('Enter the percentage of gross salary'),

                                    Placeholder::make('gross_example')
                                        ->label('Example')
                                        ->content(function (callable $get) {
                                            $percentage = $get('calculation_details.value') ?: 0;
                                            return "For an employee with gross salary of ₦150,000, this item will be <strong>₦" . number_format(150000 * $percentage / 100, 2) . "</strong> ({$percentage}% of gross).";
                                        }),
                                ])
                                ->columnSpanFull()
                                ->visible(fn(callable $get) => $get('calculation_type') === 'percentage_of_gross')
                                ->columns(1),

                            // PERCENTAGE OF SPECIFIC ITEM
                            Section::make('Percentage of Another Item')
                                ->schema([
                                    FormsSelect::make('calculation_details.base_item')
                                        ->label('Base Item')
                                        ->options(function () {
                                            return PayrollItemType::where('is_active', true)
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        })
                                        ->required()
                                        ->searchable()
                                        ->helperText('Select the item this percentage is based on'),

                                    TextInput::make('calculation_details.value')
                                        ->label('Percentage')
                                        ->numeric()
                                        ->suffix('%')
                                        ->required()
                                        ->placeholder('e.g., 50')
                                        ->helperText('Enter the percentage value'),

                                    Placeholder::make('percentage_item_example')
                                        ->label('Example')
                                        ->content(function (callable $get) {
                                            $baseItem = PayrollItemType::find($get('calculation_details.base_item'));
                                            $percentage = $get('calculation_details.value') ?: 0;
                                            $baseItemName = $baseItem ? $baseItem->name : 'selected item';
                                            return "If an employee's {$baseItemName} is ₦100,000, this item will be <strong>₦" . number_format(100000 * $percentage / 100, 2) . "</strong> ({$percentage}% of {$baseItemName}).";
                                        }),
                                ])
                                ->columnSpanFull()
                                ->visible(fn(callable $get) => $get('calculation_type') === 'percentage_of_item')
                                ->columns(2),

                            // GRADE BASED
                            Section::make('Grade-Based Amount')
                                ->schema([
                                    Placeholder::make('grade_based_instructions')
                                        ->label('Instructions')
                                        ->content("
                                            Set different amounts for different grade levels. Use the following formats:
                                            
                                            <ul>
                                                <li><strong>Single grade</strong>: Enter just the grade number (e.g., \"4\")</li>
                                                <li><strong>Grade range</strong>: Enter range with dash (e.g., \"1-5\")</li>
                                                <li><strong>Grade and above</strong>: Add plus sign (e.g., \"10+\")</li>
                                            </ul>
                                            
                                            For example, to set Transport Allowance:
                                            <ul>
                                                <li>Key: <strong>1-5</strong> → Value: <strong>15000</strong></li>
                                                <li>Key: <strong>6-10</strong> → Value: <strong>22000</strong></li>
                                                <li>Key: <strong>11+</strong> → Value: <strong>30000</strong></li>
                                            </ul>
                                        "),

                                    KeyValue::make('calculation_details.grade_rules')
                                        ->label('Grade-Based Rules')
                                        ->keyLabel('Grade/Range')
                                        ->valueLabel('Amount (₦)')
                                        ->keyPlaceholder('e.g., 1-5 or 10+')
                                        ->valuePlaceholder('e.g., 25000')
                                        ->required()
                                        ->reorderable()
                                        ->columnSpanFull(),

                                    Placeholder::make('grade_based_example')
                                        ->label('Example')
                                        ->content(function (callable $get) {
                                            $rules = $get('calculation_details.grade_rules') ?: [];
                                            if (empty($rules))
                                                return "No grade rules defined yet.";

                                            $html = "Here's how this will be applied:\n<ul>";
                                            foreach ($rules as $range => $amount) {
                                                $html .= "<li>Employee on Grade Level <strong>{$range}</strong>: Will receive <strong>₦" . number_format((float) $amount, 2) . "</strong></li>";
                                            }
                                            $html .= "</ul>";
                                            return $html;
                                        }),
                                ])
                                ->columnSpanFull()
                                ->visible(fn(callable $get) => $get('calculation_type') === 'grade_based')
                                ->columns(1),

                            // SALARY STRUCTURE BASED
                            Section::make('Salary Structure Based')
                                ->schema([
                                    ToggleButtons::make('calculation_details.structure_type')
                                        ->label('Salary Structure Type')
                                        ->options([
                                            'monthly' => 'Monthly Salary Structure',
                                            'annual' => 'Annual Salary Structure',
                                        ])
                                        ->default('monthly')
                                        ->required()
                                        ->helperText('Select which salary structure table to use'),

                                    Toggle::make('calculation_details.auto_fetch')
                                        ->label('Automatically fetch from salary structure')
                                        ->helperText('If enabled, the system will pull the value from the appropriate grade/step in the salary structure table')
                                        ->default(true),

                                    Placeholder::make('salary_structure_example')
                                        ->label('How This Works')
                                        ->content("
                                            This will automatically look up the appropriate amount from the salary structure based on employee grade and step.
                                            
                                            For example:
                                            <ul>
                                                <li>An employee on GL 7 Step 5 will get the exact amount specified in the Grade 7, Step 5 cell of the salary structure.</li>
                                                <li>This is typically used for Basic Salary but can be used for any standard scale-based allowance.</li>
                                            </ul>
                                        "),
                                ])
                                ->columnSpanFull()
                                ->visible(fn(callable $get) => $get('calculation_type') === 'salary_structure')
                                ->columns(1),

                            // PERCENTAGE OF GROSS WITH EXCLUSIONS
                            Section::make('Percentage of Gross with Exclusions')
                                ->schema([
                                    TextInput::make('calculation_details.value')
                                        ->label('Percentage')
                                        ->numeric()
                                        ->suffix('%')
                                        ->required()
                                        ->placeholder('e.g., 4')
                                        ->helperText('Enter the percentage of gross salary after exclusions'),

                                    CheckboxList::make('calculation_details.excluded_items')
                                        ->label('Items to Exclude')
                                        ->options(function () {
                                            return PayrollItemType::where('is_active', true)
                                                ->where('type', 'earning')
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        })
                                        ->helperText('Select all earnings that should be excluded from the gross calculation')
                                        ->columns(2),

                                    Placeholder::make('exclusions_example')
                                        ->label('Example')
                                        ->content(function (callable $get) {
                                            $percentage = $get('calculation_details.value') ?: 0;
                                            $excludedItems = $get('calculation_details.excluded_items') ?? [];

                                            if (empty($excludedItems)) {
                                                return "For an employee with gross salary of ₦150,000, this item will be <strong>₦" . number_format(150000 * $percentage / 100, 2) . "</strong> ({$percentage}% of gross).";
                                            } else {
                                                $excludedItemNames = PayrollItemType::whereIn('id', $excludedItems)
                                                    ->pluck('name')
                                                    ->implode(', ');
                                                return "For an employee with gross salary of ₦150,000, if the excluded items ({$excludedItemNames}) total ₦50,000, this item will be <strong>₦" . number_format(100000 * $percentage / 100, 2) . "</strong> ({$percentage}% of the remaining ₦100,000).";
                                            }
                                        }),
                                ])
                                ->columnSpanFull()
                                ->visible(fn(callable $get) => $get('calculation_type') === 'percentage_of_gross_with_exclusions')
                                ->columns(1),

                            // SUM OF ITEMS
                            Section::make('Sum of Multiple Items')
                                ->schema([
                                    CheckboxList::make('calculation_details.items_to_sum')
                                        ->label('Items to Sum')
                                        ->options(function () {
                                            return PayrollItemType::where('is_active', true)
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        })
                                        ->required()
                                        ->columns(2)
                                        ->helperText('Select all items that should be included in the sum'),

                                    Placeholder::make('sum_example')
                                        ->label('Example')
                                        ->content(function (callable $get) {
                                            $includedItems = $get('calculation_details.items_to_sum') ?? [];

                                            if (empty($includedItems)) {
                                                return "No items selected yet.";
                                            } else {
                                                $includedItemNames = PayrollItemType::whereIn('id', $includedItems)
                                                    ->pluck('name')
                                                    ->implode(' + ');
                                                return "This will be calculated as the total sum of: {$includedItemNames}.<br>For example, if these items total to ₦85,000, then this item will be <strong>₦85,000</strong>.";
                                            }
                                        }),
                                ])
                                ->columnSpanFull()
                                ->visible(fn(callable $get) => $get('calculation_type') === 'sum_of_items')
                                ->columns(1),

                            // PERCENTAGE OF SUM
                            Section::make('Percentage of Sum of Items')
                                ->schema([
                                    CheckboxList::make('calculation_details.items_to_sum')
                                        ->label('Items to Sum')
                                        ->options(function () {
                                            return PayrollItemType::where('is_active', true)
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        })
                                        ->required()
                                        ->columns(2)
                                        ->helperText('Select all items that should be included in the sum'),

                                    TextInput::make('calculation_details.percentage')
                                        ->label('Percentage')
                                        ->numeric()
                                        ->suffix('%')
                                        ->required()
                                        ->placeholder('e.g., 8')
                                        ->helperText('Enter the percentage to apply to the sum'),

                                    Placeholder::make('percentage_sum_example')
                                        ->label('Example')
                                        ->content(function (callable $get) {
                                            $includedItems = $get('calculation_details.items_to_sum') ?? [];
                                            $percentage = $get('calculation_details.percentage') ?: 0;

                                            if (empty($includedItems)) {
                                                return "No items selected yet.";
                                            } else {
                                                $includedItemNames = PayrollItemType::whereIn('id', $includedItems)
                                                    ->pluck('name')
                                                    ->implode(' + ');
                                                return "This will be calculated as {$percentage}% of the sum of: {$includedItemNames}.<br>For example, if these items total to ₦85,000, then this item will be <strong>₦" . number_format(85000 * $percentage / 100, 2) . "</strong>.";
                                            }
                                        }),
                                ])
                                ->columnSpanFull()
                                ->visible(fn(callable $get) => $get('calculation_type') === 'percentage_of_sum')
                                ->columns(1),
                                
                            // ANNIVERSARY MONTH-BASED ITEMS
                            Section::make('Anniversary Month-Based Configuration')
                                ->schema([
                                    Toggle::make('calculation_details.anniversary_only')
                                        ->label('Show only in anniversary month')
                                        ->helperText('When enabled, this item will only appear in the staff\'s anniversary month as specified in their profile')
                                        ->default(true)
                                        ->required(),
                                        
                                    Placeholder::make('anniversary_item_instructions')
                                        ->label('How this works')
                                        ->content('This item will only be included in payroll calculations during the staff\'s anniversary month. You must select a calculation method below for how the amount should be determined when it does appear.'),
                                        
                                    FormsSelect::make('calculation_details.amount_method')
                                        ->label('Amount Calculation Method')
                                        ->options([
                                            'fixed' => 'Fixed Amount',
                                            'grade_based' => 'Grade-Based Amount',
                                            'percentage_of_basic' => 'Percentage of Basic Salary',
                                        ])
                                        ->default('grade_based')
                                        ->reactive()
                                        ->required(),
                                    
                                    // Fixed amount option
                                    TextInput::make('calculation_details.fixed_amount')
                                        ->label('Fixed Amount (₦)')
                                        ->numeric()
                                        ->required()
                                        ->prefix('₦')
                                        ->visible(fn(callable $get) => $get('calculation_details.amount_method') === 'fixed'),
                                    
                                    // Grade-based amount option
                                    KeyValue::make('calculation_details.grade_rules')
                                        ->label('Grade-Based Rules')
                                        ->keyLabel('Grade/Range')
                                        ->valueLabel('Amount (₦)')
                                        ->keyPlaceholder('e.g., 1-5 or 10+')
                                        ->valuePlaceholder('e.g., 25000')
                                        ->required()
                                        ->visible(fn(callable $get) => $get('calculation_details.amount_method') === 'grade_based')
                                        ->columnSpanFull(),
                                    
                                    // Percentage of basic option
                                    TextInput::make('calculation_details.percentage_value')
                                        ->label('Percentage of Basic Salary')
                                        ->numeric()
                                        ->suffix('%')
                                        ->required()
                                        ->visible(fn(callable $get) => $get('calculation_details.amount_method') === 'percentage_of_basic'),
                                ])
                                ->columnSpanFull()
                                ->visible(fn(callable $get) => $get('calculation_type') === 'anniversary_based')
                                ->columns(1),
                                
                            // LEAVE GRANT (20% of Annual Basic Salary)
                            Section::make('Leave Grant Configuration')
                                ->schema([
                                    Toggle::make('calculation_details.anniversary_only')
                                        ->label('Show only in anniversary month')
                                        ->helperText('When enabled, this item will only appear in the staff\'s anniversary month as specified in their profile')
                                        ->default(true)
                                        ->required(),
                                        
                                    Placeholder::make('leave_grant_instructions')
                                        ->label('Leave Grant Calculation')
                                        ->content('Leave Grant is calculated as 20% of the staff\'s annual basic salary. It will only appear in the staff\'s anniversary month.'),
                                        
                                    Placeholder::make('leave_grant_example')
                                        ->label('Example')
                                        ->content('For a staff on GL 8/5 with monthly basic salary of ₦170,000:<br>
                                        - Annual Basic = ₦170,000 × 12 = ₦2,040,000<br>
                                        - Leave Grant = 20% of ₦2,040,000 = ₦408,000<br><br>
                                        This amount will only be included in the payroll during the staff\'s anniversary month.'),
                                ])
                                ->columnSpanFull()
                                ->visible(fn(callable $get) => $get('calculation_type') === 'leave_grant')
                                ->columns(1),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Item Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('code')
                    ->label('Chart Account Code')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) return 'Not linked';
                        
                        $account = \App\Models\Finance\ChartOfAccount::where('code', $state)->first();
                        return $account ? "{$state} - {$account->name}" : $state;
                    })
                    ->color(fn($state) => $state ? 'success' : 'gray')
                    ->icon(fn($state) => $state ? 'heroicon-o-link' : 'heroicon-o-x-mark')
                    ->tooltip(function ($state, $record) {
                        if (!$state) return 'Not linked to any chart of account';
                        
                        $account = \App\Models\Finance\ChartOfAccount::where('code', $state)->first();
                        return $account ? "Linked to: {$account->name}" : "Chart account not found";
                    }),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->icon(fn(string $state): string => match ($state) {
                        'earning' => 'heroicon-o-arrow-trending-up',
                        'deduction' => 'heroicon-o-arrow-trending-down',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'earning' => 'success',
                        'deduction' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('calculation_type')
                    ->label('Calculation Method')
                    ->formatStateUsing(fn($state) => match ($state) {
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
                        default => $state,
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'fixed_amount' => 'heroicon-o-currency-dollar',
                        'percentage_of_gross' => 'heroicon-o-calculator',
                        'percentage_of_item' => 'heroicon-o-variable',
                        'grade_based' => 'heroicon-o-scale',
                        'salary_structure' => 'heroicon-o-table-cells',
                        'percentage_of_gross_with_exclusions' => 'heroicon-o-document-minus',
                        'sum_of_items' => 'heroicon-o-plus-circle',
                        'percentage_of_sum' => 'heroicon-o-plus',
                        'anniversary_based' => 'heroicon-o-calendar',
                        'leave_grant' => 'heroicon-o-banknotes',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->label('Created On')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'earning' => 'Earning',
                        'deduction' => 'Deduction',

                    ]),
                Tables\Filters\SelectFilter::make('calculation_type')
                    ->label('Calculation Method')
                    ->options([
                        'fixed_amount' => 'Fixed Amount',
                        'percentage_of_gross' => 'Percentage of Gross',
                        'percentage_of_item' => 'Percentage of Item',
                        'grade_based' => 'Grade-Based',
                        'salary_structure' => 'Salary Structure',
                        'percentage_of_gross_with_exclusions' => 'Percentage with Exclusions',
                        'sum_of_items' => 'Sum of Items',
                        'percentage_of_sum' => 'Percentage of Sum',
                        'anniversary_based' => 'Anniversary Month-Based',
                        'leave_grant' => 'Leave Grant',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('preview')
                        ->label('Preview Calculation')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->modalHeading(fn($record) => "Preview: {$record->name}")
                        ->modalDescription('This shows how this payroll item would be calculated for different grade levels and steps.')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->form([
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Select::make('grade')
                                        ->label('Grade Level')
                                        ->options(array_combine(range(1, 15), range(1, 15)))
                                        ->default(8)
                                        ->required(),
                                    Forms\Components\Select::make('step')
                                        ->label('Step')
                                        ->options(array_combine(range(1, 15), range(1, 15)))
                                        ->default(5)
                                        ->required(),
                                ])
                                ->columns(2),

                            Forms\Components\Placeholder::make('preview_results')
                                ->label('Calculation Preview')
                                ->content(function (array $data, $record) {
                                    if (empty($data['grade']) || empty($data['step'])) {
                                        return 'Please select a grade and step to see the calculation.';
                                    }

                                    $grade = (int) $data['grade'];
                                    $step = (int) $data['step'];

                                    // Get the basic salary from the grade/step
                                    $basicSalary = self::getBasicSalaryFromGradeStep($grade, $step);
                                    if (!$basicSalary) {
                                        return "Could not determine Basic Salary for Grade {$grade} Step {$step}.";
                                    }

                                    // Calculate based on the calculation method
                                    $calculationMethod = $record->calculation_details['method'] ?? null;
                                    $result = 0;
                                    $explanation = "";

                                    switch ($calculationMethod) {
                                        case 'fixed':
                                            $result = $record->calculation_details['value'] ?? 0;
                                            $explanation = "Fixed amount of ₦" . number_format($result, 2);
                                            break;

                                        case 'percentage':
                                            $percentage = $record->calculation_details['value'] ?? 0;
                                            $baseItemId = $record->calculation_details['base_item'] ?? null;

                                            if ($record->calculation_type === 'percentage_of_gross') {
                                                // Estimate gross as 2.5 times basic for preview
                                                $grossEstimate = $basicSalary * 2.5;
                                                $result = $grossEstimate * ($percentage / 100);
                                                $explanation = "{$percentage}% of estimated Gross Salary (₦" . number_format($grossEstimate, 2) . ")";
                                            } elseif ($record->calculation_type === 'percentage_of_item' && $baseItemId) {
                                                $baseItem = \App\Models\Finance\PayrollItemType::find($baseItemId);
                                                if ($baseItem && $baseItem->name === 'Basic Salary') {
                                                    $result = $basicSalary * ($percentage / 100);
                                                    $explanation = "{$percentage}% of Basic Salary (₦" . number_format($basicSalary, 2) . ")";
                                                } else {
                                                    $baseItemName = $baseItem ? $baseItem->name : 'Unknown Item';
                                                    $explanation = "{$percentage}% of {$baseItemName} (exact amount would depend on that item's value)";
                                                }
                                            }
                                            break;

                                        case 'grade_based':
                                            $gradeRules = $record->calculation_details['grade_rules'] ?? [];
                                            $amount = null;

                                            // Try exact grade match first
                                            if (isset($gradeRules[(string) $grade])) {
                                                $amount = $gradeRules[(string) $grade];
                                                $explanation = "Exact match for Grade {$grade}";
                                            } else {
                                                // Check ranges and plus notation
                                                foreach ($gradeRules as $range => $value) {
                                                    // Check if it's a plus notation (e.g., "10+")
                                                    if (substr($range, -1) === '+') {
                                                        $minGrade = (int) substr($range, 0, -1);
                                                        if ($grade >= $minGrade) {
                                                            $amount = $value;
                                                            $explanation = "Matched range {$range}";
                                                            break;
                                                        }
                                                    }
                                                    // Check if it's a range (e.g., "1-5")
                                                    elseif (str_contains($range, '-')) {
                                                        list($min, $max) = explode('-', $range);
                                                        if ($grade >= (int) $min && $grade <= (int) $max) {
                                                            $amount = $value;
                                                            $explanation = "Matched range {$range}";
                                                            break;
                                                        }
                                                    }
                                                }
                                            }

                                            $result = $amount ?? 0;
                                            if (!$amount) {
                                                $explanation = "No matching grade rule found for Grade {$grade}";
                                            }
                                            break;

                                        case 'salary_structure':
                                            $result = $basicSalary;
                                            $explanation = "Basic Salary for Grade {$grade} Step {$step} from salary structure";
                                            break;

                                        default:
                                            $explanation = "This calculation method requires additional context from other payroll items";
                                            break;
                                    }

                                    $output = "<div class='mt-4 p-4 bg-gray-50 rounded-lg'>";
                                    $output .= "<div class='text-lg font-medium text-primary-600'>₦" . number_format($result, 2) . "</div>";
                                    $output .= "<div class='text-sm text-gray-600 mt-1'>{$explanation}</div>";

                                    // Additional context
                                    $output .= "<div class='text-xs text-gray-500 mt-3'>Based on:</div>";
                                    $output .= "<ul class='text-xs text-gray-500 list-disc pl-5'>";
                                    $output .= "<li>Grade Level: {$grade}</li>";
                                    $output .= "<li>Step: {$step}</li>";
                                    $output .= "<li>Basic Salary: ₦" . number_format($basicSalary, 2) . "</li>";
                                    $output .= "</ul>";
                                    $output .= "</div>";

                                    return new \Illuminate\Support\HtmlString($output);
                                })
                                ->columnSpanFull(),
                        ])
                        ->action(function () {
                            // This action doesn't perform any operation, it just shows the preview
                        }),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Basic Information')
                    ->schema([
                        \Filament\Infolists\Components\Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Payroll Item Name')
                                    ->size('text-xl font-bold'),

                                TextEntry::make('type')
                                    ->label('Item Type')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'earning' => 'success',
                                        'deduction' => 'danger',
                                        default => 'gray',
                                    }),
                            ]),

                        \Filament\Infolists\Components\Grid::make(2)
                            ->schema([
                                IconEntry::make('is_active')
                                    ->label('Status')
                                    ->boolean(),

                                TextEntry::make('calculation_type')
                                    ->label('Calculation Method')
                                    ->formatStateUsing(fn($state) => match ($state) {
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
                                        default => $state,
                                    }),
                            ]),
                    ])
                    ->collapsible(false),

                \Filament\Infolists\Components\Section::make('Calculation Details')
                    ->schema([
                        // For Fixed Amount
                        TextEntry::make('calculation_details.value')
                            ->label('Fixed Amount')
                            ->prefix('₦')
                            ->formatStateUsing(fn($state) => number_format((float) $state, 2))
                            ->visible(fn($record) => $record->calculation_type === 'fixed_amount'),

                        // For Percentage methods
                        TextEntry::make('calculation_details.value')
                            ->label('Percentage')
                            ->suffix('%')
                            ->visible(fn($record) => in_array($record->calculation_type, [
                                'percentage_of_gross',
                                'percentage_of_item',
                                'percentage_of_gross_with_exclusions'
                            ])),

                        TextEntry::make('calculation_details.base_item')
                            ->label('Based On')
                            ->visible(fn($record) => $record->calculation_type === 'percentage_of_item')
                            ->formatStateUsing(function ($state) {
                                $item = PayrollItemType::find($state);
                                return $item ? $item->name : 'Unknown Item';
                            }),

                        // For Grade-Based method
                        TextEntry::make('calculation_details.grade_rules')
                            ->label('Grade-Based Rules')
                            ->visible(fn($record) => $record->calculation_type === 'grade_based')
                            ->formatStateUsing(function ($state) {
                                if (!is_array($state))
                                    return 'No rules defined';

                                $formatted = '';
                                foreach ($state as $range => $amount) {
                                    $formatted .= "<div class='mb-1'>
                                        <span class='font-semibold'>Grade {$range}:</span> 
                                        <span class='ml-2 text-primary-600'>₦" . number_format((float) $amount, 2) . "</span>
                                    </div>";
                                }

                                return new \Illuminate\Support\HtmlString($formatted);
                            }),

                        // For Salary Structure
                        TextEntry::make('calculation_details.structure_type')
                            ->label('Salary Structure')
                            ->formatStateUsing(fn($state) => $state === 'monthly' ? 'Monthly Salary Structure' : 'Annual Salary Structure')
                            ->visible(fn($record) => $record->calculation_type === 'salary_structure'),

                        IconEntry::make('calculation_details.auto_fetch')
                            ->label('Auto-fetch from structure')
                            ->boolean()
                            ->visible(fn($record) => $record->calculation_type === 'salary_structure'),

                        // For Sum methods
                        TextEntry::make('calculation_details.items_to_sum')
                            ->label('Items Included in Sum')
                            ->visible(fn($record) => in_array($record->calculation_type, ['sum_of_items', 'percentage_of_sum']))
                            ->formatStateUsing(function ($state) {
                                if (!is_array($state))
                                    return 'No items selected';

                                $items = PayrollItemType::whereIn('id', $state)->pluck('name')->toArray();
                                return implode('<br>', $items);
                            })
                            ->html(),

                        TextEntry::make('calculation_details.percentage')
                            ->label('Percentage of Sum')
                            ->suffix('%')
                            ->visible(fn($record) => $record->calculation_type === 'percentage_of_sum'),

                        // For Anniversary-based items
                        IconEntry::make('calculation_details.anniversary_only')
                            ->label('Only in Anniversary Month')
                            ->boolean()
                            ->visible(fn($record) => in_array($record->calculation_type, ['anniversary_based', 'leave_grant'])),
                            
                        TextEntry::make('calculation_details.amount_method')
                            ->label('Amount Method')
                            ->formatStateUsing(fn($state) => match ($state) {
                                'fixed' => 'Fixed Amount',
                                'grade_based' => 'Grade-Based Amount',
                                'percentage_of_basic' => 'Percentage of Basic Salary',
                                default => $state,
                            })
                            ->visible(fn($record) => $record->calculation_type === 'anniversary_based'),
                            
                        TextEntry::make('calculation_details.fixed_amount')
                            ->label('Fixed Amount')
                            ->prefix('₦')
                            ->formatStateUsing(fn($state) => number_format((float) $state, 2))
                            ->visible(fn($record) => $record->calculation_type === 'anniversary_based' && 
                                isset($record->calculation_details['amount_method']) && 
                                $record->calculation_details['amount_method'] === 'fixed'),
                                
                        TextEntry::make('calculation_details.percentage_value')
                            ->label('Percentage of Basic')
                            ->suffix('%')
                            ->visible(fn($record) => $record->calculation_type === 'anniversary_based' && 
                                isset($record->calculation_details['amount_method']) && 
                                $record->calculation_details['amount_method'] === 'percentage_of_basic'),
                                
                        // For Leave Grant
                        TextEntry::make('leave_grant_info')
                            ->label('Leave Grant Calculation')
                            ->formatStateUsing(fn() => 'Calculated as 20% of annual basic salary')
                            ->visible(fn($record) => $record->calculation_type === 'leave_grant'),
                    ])
                    ->collapsible()
                    ->persistCollapsed(false),

                \Filament\Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible(),
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
            'index' => Pages\ListPayrollItemTypes::route('/'),
            'create' => Pages\CreatePayrollItemType::route('/create'),
            'view' => Pages\ViewPayrollItemType::route('/{record}'),
            'edit' => Pages\EditPayrollItemType::route('/{record}/edit'),
        ];
    }

    /**
     * Helper method to get basic salary from grade and step
     * Used for preview calculations
     */
    /**
     * Get basic salary from grade and step
     */
    protected static function getBasicSalaryFromGradeStep($grade, $step)
    {
        if (!$grade || !$step) {
            return 0.00;
        }

        // Check multiple possible locations for the CSV file
        $csvPath = storage_path('app/monthly.csv');
        if (!file_exists($csvPath)) {
            $csvPath = base_path('monthly.csv');
        }

        if (!file_exists($csvPath)) {
            // Debug log to check where we're looking
            \Illuminate\Support\Facades\Log::error('Salary structure CSV file not found', [
                'checked_paths' => [
                    storage_path('app/monthly.csv'),
                    base_path('monthly.csv'),
                ]
            ]);

            // Use fallback values if CSV not found
            $fallbackValues = [
                1 => [35560.73, 37038.56, 38516.39, 39994.21, 41472.04, 42949.87, 44427.70, 45905.53],
                2 => [42973.67, 44451.50, 45929.33, 47407.16, 48884.99, 50362.81, 51840.64, 53318.47],
                3 => [46286.80, 47975.21, 49663.63, 51352.04, 53040.46, 54728.87, 56417.29, 58105.70],
                4 => [53468.26, 55156.67, 56845.09, 58533.50, 60221.92, 61910.33, 63598.75, 65287.16],
                5 => [64516.72, 66836.46, 69156.20, 71475.93, 73795.67, 76115.41, 78435.15, 80754.89],
                6 => [77729.19, 80048.92, 82368.66, 84688.40, 87008.14, 89327.88, 91647.61, 93967.35],
                7 => [127604.60, 131750.64, 135896.69, 140042.73, 144188.77, 148334.81, 152480.85, 156626.90],
                8 => [149644.20, 154673.81, 159703.42, 164733.03, 169762.64, 174792.25, 179821.86, 184851.47],
                9 => [170418.83, 177002.77, 183586.71, 190170.65, 196754.59, 203338.53, 209922.47, 216506.41],
                10 => [191191.63, 198654.09, 206116.55, 213579.01, 221041.47, 228503.93, 235966.39, 243428.85],
                11 => [237865.91, 246880.83, 255895.75, 264910.67, 273925.59, 282940.51, 291955.43, 300970.35],
                12 => [267407.22, 277312.15, 287217.08, 297122.01, 307026.94, 316931.87, 326836.80, 336741.73],
                13 => [302926.50, 313679.77, 324433.04, 335186.31, 345939.58, 356692.85, 367446.12, 378199.39],
                14 => [346071.44, 359021.12, 371970.80, 384920.48, 397870.16, 410819.84, 423769.52, 436719.20],
                15 => [506909.95, 525613.53, 544317.11, 563020.69, 581724.27, 600427.85, 619131.43, 637835.01]
            ];

            // Get the value from our fallback array
            if (isset($fallbackValues[$grade]) && isset($fallbackValues[$grade][$step - 1])) {
                return number_format($fallbackValues[$grade][$step - 1], 2, '.', '');
            }

            return number_format(50000.00, 2, '.', ''); // Default fallback
        }

        try {
            $file = fopen($csvPath, 'r');

            // Skip header row
            fgetcsv($file);

            // Find the row for this grade level
            while (($row = fgetcsv($file)) !== false) {
                $rowGrade = (int) $row[0];

                if ($rowGrade === $grade) {
                    // Steps are in columns 3-14 (CSV indexes 2-13)
                    $stepIndex = $step + 1; // +1 because the first step is at index 2

                    if (isset($row[$stepIndex])) {
                        $salary = (float) str_replace(',', '', $row[$stepIndex]);
                        fclose($file);

                        // Format to 2 decimal places
                        return number_format($salary, 2, '.', '');
                    }

                    break;
                }
            }

            fclose($file);
            \Illuminate\Support\Facades\Log::warning('Grade/step not found in salary structure', ['grade' => $grade, 'step' => $step]);
            return number_format(50000.00, 2, '.', ''); // Default value if grade/step not found

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error reading salary structure: ' . $e->getMessage(), [
                'exception' => $e,
                'csv_path' => $csvPath
            ]);
            return number_format(50000.00, 2, '.', ''); // Default value in case of error
        }
    }
}
