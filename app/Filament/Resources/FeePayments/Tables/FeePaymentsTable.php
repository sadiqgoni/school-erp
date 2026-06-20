<?php

namespace App\Filament\Resources\FeePayments\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
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
                TextColumn::make('receipt_number')
                    ->label('Receipt')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record): ?string => $record->payment_date?->format('d M Y')),
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable()
                    ->description(fn ($record): ?string => $record->student?->admission_number),
                TextColumn::make('studentInvoice.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('amount')
                    ->label('Amount received')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString())
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'gray',
                        'bank_transfer' => 'info',
                        'pos', 'card' => 'primary',
                        'online' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('bankAccount.bank_name')
                    ->label('Bank')
                    ->placeholder('Not set')
                    ->toggleable(),
                TextColumn::make('assetAccount.name')
                    ->label('Debit account')
                    ->placeholder('Auto/default')
                    ->toggleable(),
                TextColumn::make('incomeAccount.name')
                    ->label('Credit account')
                    ->placeholder('Auto/default')
                    ->toggleable(),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->title()->toString())
                    ->color(fn (string $state): string => match ($state) {
                    'confirmed' => 'success',
                    'pending' => 'warning',
                    'failed', 'reversed' => 'danger',
                    default => 'gray',
                }),
                IconColumn::make('acknowledged_at')
                    ->label('Seen')
                    ->boolean()
                    ->state(fn ($record): bool => filled($record->acknowledged_at))
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),
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
                TernaryFilter::make('acknowledged_at')
                    ->label('Seen')
                    ->nullable(),
            ])
            ->recordActions([
                Action::make('confirmSeen')
                    ->label('Confirm seen')
                    ->icon('heroicon-m-check-circle')
                    ->color('danger')
                    ->visible(fn ($record): bool => $record->status === 'confirmed' && blank($record->acknowledged_at))
                    ->requiresConfirmation()
                    ->modalHeading('Confirm this payment as seen?')
                    ->modalDescription('This removes this payment from the sidebar notification count. It does not change the receipt or accounting entries.')
                    ->action(function ($record): void {
                        $record->forceFill([
                            'acknowledged_at' => now(),
                            'acknowledged_by_id' => Filament::auth()->id(),
                        ])->save();

                        Notification::make()
                            ->success()
                            ->title('Payment confirmed as seen')
                            ->body("{$record->receipt_number} has been cleared from new payments.")
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('payment_date', 'desc')
            ->striped();
    }
}
