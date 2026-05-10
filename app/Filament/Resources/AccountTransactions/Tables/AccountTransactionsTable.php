<?php

namespace App\Filament\Resources\AccountTransactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AccountTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('transaction_number')->label('No.')->searchable()->sortable(),
                TextColumn::make('transaction_date')->date()->sortable(),
                TextColumn::make('ledgerAccount.name')->label('Account')->searchable()->sortable(),
                TextColumn::make('direction')->badge()->color(fn (string $state): string => $state === 'debit' ? 'success' : 'warning'),
                TextColumn::make('amount')->money('NGN')->sortable(),
                TextColumn::make('description')->searchable()->limit(40),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                SelectFilter::make('direction')->options([
                    'debit' => 'Debit',
                    'credit' => 'Credit',
                ]),
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'posted' => 'Posted',
                    'void' => 'Void',
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
