<?php

namespace App\Filament\Resources\StudentDiscounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class StudentDiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record): ?string => $record->notes),
                TextColumn::make('type')->badge(),
                TextColumn::make('value')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record): string => $record->type === 'percentage'
                        ? number_format((float) $state, 2).'%'
                        : 'NGN '.number_format((float) $state, 2)),
                TextColumn::make('starts_on')->date()->toggleable(),
                TextColumn::make('ends_on')->date()->toggleable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
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
