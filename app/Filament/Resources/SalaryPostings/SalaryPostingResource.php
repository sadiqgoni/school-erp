<?php

namespace App\Filament\Resources\SalaryPostings;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\SalaryPostings\Pages\ListSalaryPostings;
use App\Filament\Resources\SalaryPostings\Pages\ViewSalaryPostingMonth;
use App\Filament\Resources\SalaryPostings\Pages\ViewSalaryPostingSheet;
use App\Models\SalaryPosting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class SalaryPostingResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = SalaryPosting::class;

    protected static ?string $navigationLabel = 'Salary Posting';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?int $navigationSort = 49;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('payroll_month')
                            ->label('Payroll Folder')
                            ->state(fn (SalaryPosting $record): string => Carbon::parse($record->payroll_month)->format('F Y'))
                            ->icon('heroicon-m-folder')
                            ->color('primary')
                            ->weight('bold')
                            ->searchable()
                            ->sortable(),
                        TextColumn::make('folder_meta')
                            ->label('Summary')
                            ->state(fn (SalaryPosting $record): string => number_format((float) ($record->staff_count ?? 0)).' staff posted')
                            ->extraAttributes(['class' => 'text-gray-500'])
                            ->size('sm'),
                    ]),
                ]),
                Panel::make([
                    Stack::make([
                        TextColumn::make('basic_total')
                            ->label('Basic')
                            ->money('NGN'),
                        TextColumn::make('earnings_total')
                            ->label('Earnings')
                            ->money('NGN')
                            ->color('success'),
                        TextColumn::make('deductions_total')
                            ->label('Deductions')
                            ->money('NGN')
                            ->color('danger'),
                        TextColumn::make('net_total')
                            ->label('Net Pay')
                            ->money('NGN')
                            ->weight('bold')
                            ->color('success'),
                        TextColumn::make('status')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'posted' ? 'success' : 'gray'),
                    ]),
                ])->collapsible(),
            ])
            ->recordActions([
                Action::make('openMonth')
                    ->label('Open')
                    ->icon('heroicon-m-folder-open')
                    ->color('primary')
                    ->url(fn (SalaryPosting $record): string => static::getUrl('month', ['month' => Carbon::parse($record->payroll_month)->toDateString()])),
                Action::make('monthPdf')
                    ->label('PDF')
                    ->icon('heroicon-m-document-arrow-down')
                    ->color('gray')
                    ->url(fn (SalaryPosting $record): string => route('salary-postings.month-pdf', [
                        'school' => $record->school_id,
                        'month' => Carbon::parse($record->payroll_month)->toDateString(),
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->defaultSort('payroll_month', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalaryPostings::route('/'),
            'month' => ViewSalaryPostingMonth::route('/month/{month}'),
            'sheet' => ViewSalaryPostingSheet::route('/month/{month}/sheet/{sheet}'),
        ];
    }
}
