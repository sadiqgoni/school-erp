<?php

namespace App\Filament\Resources\FeePayments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FeePaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('receipt_number')->searchable()->sortable(),
                TextColumn::make('student.admission_number')->label('Admission no.')->searchable(),
                TextColumn::make('studentInvoice.invoice_number')->label('Invoice')->searchable(),
                TextColumn::make('payment_date')->date()->sortable(),
                TextColumn::make('amount')->money('NGN')->sortable(),
                TextColumn::make('payment_method')->badge(),
                TextColumn::make('bankAccount.bank_name')->label('Bank')->toggleable(),
                TextColumn::make('assetAccount.name')->label('Asset account')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('incomeAccount.name')->label('Income account')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'confirmed' => 'success',
                    'pending' => 'warning',
                    'failed', 'reversed' => 'danger',
                    default => 'gray',
                }),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                SelectFilter::make('payment_method')->options([
                    'cash' => 'Cash',
                    'bank_transfer' => 'Bank transfer',
                    'pos' => 'POS',
                    'card' => 'Card',
                    'online' => 'Online',
                ]),
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'confirmed' => 'Confirmed',
                    'failed' => 'Failed',
                    'reversed' => 'Reversed',
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
