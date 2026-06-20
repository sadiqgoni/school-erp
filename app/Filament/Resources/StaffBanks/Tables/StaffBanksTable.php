<?php

namespace App\Filament\Resources\StaffBanks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class StaffBanksTable
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
                    ->label('Bank')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record): ?string => $record->notes),
                TextColumn::make('code')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('staff_count')
                    ->counts('staff')
                    ->label('Staff')
                    ->sortable(),
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
