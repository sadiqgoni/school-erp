<?php

namespace App\Filament\Resources\PayrollSheets;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\PayrollSheets\Pages\CreatePayrollSheet;
use App\Filament\Resources\PayrollSheets\Pages\EditPayrollSheet;
use App\Filament\Resources\PayrollSheets\Pages\ListPayrollSheets;
use App\Filament\Support\SchoolSelect;
use App\Models\PayrollSheet;
use App\Models\Staff;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PayrollSheetResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = PayrollSheet::class;

    protected static ?string $navigationLabel = 'Payroll Sheets';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|\UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?int $navigationSort = 47;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payroll Sheet')
                ->schema([
                    SchoolSelect::make(),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Management, Sheet 1, Operations'),
                    Toggle::make('is_active')
                        ->default(true),
                    Select::make('staff_ids')
                        ->label('Assign staff')
                        ->multiple()
                        ->options(fn (): array => Staff::query()
                            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                            ->orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn (Staff $staff): array => [$staff->getKey() => "{$staff->full_name} ({$staff->staff_number})"])
                            ->all())
                        ->preload()
                        ->searchable()
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->weight('semibold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('staff_count')
                    ->label('Staff')
                    ->counts('staff')
                    ->badge()
                    ->color('primary'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollSheets::route('/'),
            'create' => CreatePayrollSheet::route('/create'),
            'edit' => EditPayrollSheet::route('/{record}/edit'),
        ];
    }
}
