<?php

namespace App\Filament\Resources\AccountTransfers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AccountTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('transfer_number')->label('No.')->searchable()->sortable(),
                TextColumn::make('transfer_date')->date()->sortable(),
                TextColumn::make('fromAccount.name')->label('From')->searchable(),
                TextColumn::make('toAccount.name')->label('To')->searchable(),
                TextColumn::make('amount')->money('NGN')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('reference')->searchable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'posted' => 'Posted',
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
