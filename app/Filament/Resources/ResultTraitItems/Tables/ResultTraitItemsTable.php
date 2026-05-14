<?php

namespace App\Filament\Resources\ResultTraitItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ResultTraitItemsTable
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
                    ->label('Trait')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'psychomotor' => 'Psychomotor',
                        default => 'Affective',
                    })
                    ->color(fn (string $state): string => $state === 'psychomotor' ? 'info' : 'success'),
                TextColumn::make('max_rating')
                    ->label('Scale')
                    ->alignCenter(),
                TextColumn::make('position')
                    ->sortable()
                    ->alignCenter(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')->options([
                    'affective' => 'Affective',
                    'psychomotor' => 'Psychomotor',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('position')
            ->emptyStateHeading('No result traits yet')
            ->emptyStateDescription('Create traits like punctuality, neatness, handwriting, creativity, and leadership for report card ratings.');
    }
}
