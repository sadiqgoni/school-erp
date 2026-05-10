<?php

namespace App\Filament\Resources\Expenses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('expense_number')->searchable()->sortable(),
                TextColumn::make('expense_date')->date()->sortable(),
                TextColumn::make('expenseCategory.name')->label('Category')->searchable()->sortable(),
                TextColumn::make('description')->searchable(),
                TextColumn::make('amount')->money('NGN')->sortable(),
                TextColumn::make('payment_method')->badge(),
                TextColumn::make('bankAccount.bank_name')->label('Bank')->toggleable(),
                TextColumn::make('assetAccount.name')->label('Paid from')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expenseAccount.name')->label('Expense account')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'approved', 'paid' => 'success',
                    'draft' => 'warning',
                    'cancelled' => 'gray',
                    default => 'gray',
                }),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                SelectFilter::make('expense_category_id')->label('Category')->relationship('expenseCategory', 'name')->searchable()->preload(),
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'approved' => 'Approved',
                    'paid' => 'Paid',
                    'cancelled' => 'Cancelled',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
