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
                TextColumn::make('invoice_number')->searchable()->sortable(),
                TextColumn::make('invoice_type')->label('Type')->badge()->sortable(),
                TextColumn::make('student.admission_number')->label('Admission no.')->searchable()->sortable(),
                TextColumn::make('student.full_name')->label('Student')->searchable(),
                TextColumn::make('discount')->money('NGN')->sortable()->toggleable(),
                TextColumn::make('total')->money('NGN')->sortable(),
                TextColumn::make('amount_paid')->money('NGN')->sortable(),
                TextColumn::make('balance')->money('NGN')->sortable(),
                TextColumn::make('status')
                    ->badge()
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
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record): string => route('student-invoices.pdf', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
