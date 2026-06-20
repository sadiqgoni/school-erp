<?php

namespace App\Filament\Resources\StudentInvoices\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StudentInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record): string => 'Issued '.$record->invoice_date?->format('d M Y')),
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable()
                    ->description(fn ($record): ?string => $record->student?->admission_number),
                TextColumn::make('student.enrollments.schoolClass.name')
                    ->label('Class')
                    ->badge()
                    ->color('info')
                    ->separator(',')
                    ->placeholder('No class')
                    ->toggleable(),
                TextColumn::make('due_date')
                    ->label('Due')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('total')
                    ->label('Invoice total')
                    ->money('NGN')
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('amount_paid')
                    ->label('Received')
                    ->money('NGN')
                    ->sortable()
                    ->weight('semibold')
                    ->color('success'),
                TextColumn::make('balance')
                    ->label('Outstanding')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($state): string => ((float) $state) > 0 ? 'warning' : 'success'),
                TextColumn::make('payment_provider')
                    ->label('Gateway')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'paystack' => 'success',
                        'simulated' => 'warning',
                        default => 'gray',
                    })
                    ->placeholder('Manual')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payment_status')
                    ->label('Gateway status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'initialized' => 'info',
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('invoice_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'standard' => 'Standard',
                        'emergency' => 'One-off',
                        default => str($state)->replace('_', ' ')->title()->toString(),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'standard' => 'primary',
                        'emergency' => 'warning',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Invoice status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString())
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'info',
                    }),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                SelectFilter::make('status')->options([
                    'unpaid' => 'Unpaid',
                    'partial' => 'Partial',
                    'paid' => 'Paid',
                    'overdue' => 'Overdue',
                    'cancelled' => 'Cancelled',
                ]),
                SelectFilter::make('invoice_type')
                    ->label('Type')
                    ->options([
                        'standard' => 'Standard invoice',
                        'emergency' => 'Emergency / one-off invoice',
                    ]),
            ])
            ->recordActions([
                Action::make('downloadPdf')
                    ->label('Invoice slip')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record): string => route('student-invoices.pdf', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('invoice_date', 'desc')
            ->striped();
    }
}
