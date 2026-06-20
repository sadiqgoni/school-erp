<?php

namespace App\Filament\Resources\PayrollSnapshots;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\PayrollSnapshots\Pages\ListPayrollSnapshots;
use App\Filament\Resources\PayrollSnapshots\Pages\ViewPayrollSnapshot;
use App\Models\Staff;
use App\Support\PayrollCalculator;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PayrollSnapshotResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = Staff::class;

    protected static ?string $navigationLabel = 'Payroll Snapshot';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?int $navigationSort = 48;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Staff')
                    ->weight('semibold')
                    ->searchable(query: function ($query, string $search) {
                        return $query
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('middle_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('staff_number', 'like', "%{$search}%");
                    })
                    ->description(fn (Staff $record): string => collect([$record->staff_number, $record->job_title])->filter()->implode('  ·  ')),
                TextColumn::make('salary_grade_level')
                    ->label('Grade')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Not set'),
                TextColumn::make('salary_step')
                    ->label('Step')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Not set'),
                TextColumn::make('payroll_basic')
                    ->label('Basic')
                    ->state(fn (Staff $record): float => PayrollCalculator::snapshotForStaff($record)['basic_salary'])
                    ->money('NGN'),
                TextColumn::make('payroll_allowances')
                    ->label('Earnings')
                    ->state(fn (Staff $record): float => PayrollCalculator::snapshotForStaff($record)['allowances_total'])
                    ->money('NGN')
                    ->color('success'),
                TextColumn::make('payroll_deductions')
                    ->label('Deductions')
                    ->state(fn (Staff $record): float => PayrollCalculator::snapshotForStaff($record)['deductions_total'])
                    ->money('NGN')
                    ->color('danger'),
                TextColumn::make('payroll_net')
                    ->label('Net Pay')
                    ->state(fn (Staff $record): float => PayrollCalculator::snapshotForStaff($record)['net_pay'])
                    ->money('NGN')
                    ->weight('bold')
                    ->color('success'),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->defaultSort('first_name');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)
                ->columnSpanFull()
                ->schema([
                    Section::make('Basic information')
                        ->schema([
                            TextEntry::make('full_name')->label('Name')->weight('bold'),
                            TextEntry::make('staff_number')->label('Staff ID')->badge()->color('primary'),
                            TextEntry::make('job_title')->label('Position')->placeholder('Not set'),
                            TextEntry::make('salary_grade_level')->label('Grade level')->badge()->placeholder('Not set'),
                            TextEntry::make('salary_step')->label('Step')->badge()->color('gray')->placeholder('Not set'),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                    Section::make('Payroll totals')
                        ->schema([
                            TextEntry::make('snapshot_basic')
                                ->label('Basic salary')
                                ->state(fn (Staff $record): float => PayrollCalculator::snapshotForStaff($record)['basic_salary'])
                                ->money('NGN'),
                            TextEntry::make('snapshot_allowances')
                                ->label('Earnings')
                                ->state(fn (Staff $record): float => PayrollCalculator::snapshotForStaff($record)['allowances_total'])
                                ->money('NGN')
                                ->color('success'),
                            TextEntry::make('snapshot_gross')
                                ->label('Gross pay')
                                ->state(fn (Staff $record): float => PayrollCalculator::snapshotForStaff($record)['gross_pay'])
                                ->money('NGN')
                                ->weight('bold'),
                            TextEntry::make('snapshot_deductions')
                                ->label('Deductions')
                                ->state(fn (Staff $record): float => PayrollCalculator::snapshotForStaff($record)['deductions_total'])
                                ->money('NGN')
                                ->color('danger'),
                            TextEntry::make('snapshot_net')
                                ->label('Net pay')
                                ->state(fn (Staff $record): float => PayrollCalculator::snapshotForStaff($record)['net_pay'])
                                ->money('NGN')
                                ->weight('bold')
                                ->color('success'),
                        ])
                        ->columns(5)
                        ->columnSpanFull(),
                    Section::make('Earnings')
                        ->schema([
                            TextEntry::make('snapshot_earnings_items')
                                ->hiddenLabel()
                                ->state(function (Staff $record): array {
                                    return collect(PayrollCalculator::snapshotForStaff($record)['allowances'])
                                        ->map(fn (array $item): string => "{$item['name']} - NGN ".number_format((float) $item['amount'], 2))
                                        ->values()
                                        ->all() ?: ['No earnings applied.'];
                                })
                                ->listWithLineBreaks(),
                        ])
                        ->columnSpanFull(),
                    Section::make('Deductions')
                        ->schema([
                            TextEntry::make('snapshot_deduction_items')
                                ->hiddenLabel()
                                ->state(function (Staff $record): array {
                                    return collect(PayrollCalculator::snapshotForStaff($record)['deductions'])
                                        ->map(fn (array $item): string => "{$item['name']} - NGN ".number_format((float) $item['amount'], 2))
                                        ->values()
                                        ->all() ?: ['No deductions applied.'];
                                })
                                ->listWithLineBreaks(),
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollSnapshots::route('/'),
            'view' => ViewPayrollSnapshot::route('/{record}'),
        ];
    }
}
