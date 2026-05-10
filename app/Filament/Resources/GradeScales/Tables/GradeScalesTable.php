<?php

namespace App\Filament\Resources\GradeScales\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GradeScalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('grade')
                    ->badge()
                    ->sortable()
                    ->color(fn (?string $state): string => match ($state) {
                        'A', 'A1' => 'success',
                        'B', 'B2', 'B3' => 'info',
                        'C', 'C4', 'C5', 'C6' => 'warning',
                        'F', 'F9' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('min_score')->numeric(decimalPlaces: 2)->sortable(),
                TextColumn::make('max_score')->numeric(decimalPlaces: 2)->sortable(),
                TextColumn::make('grade_point')->numeric(decimalPlaces: 2)->sortable()->toggleable(),
                TextColumn::make('remark')->searchable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('school')->relationship('school', 'name')->searchable()->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('min_score', 'desc')
            ->emptyStateHeading('No grade scale yet')
            ->emptyStateDescription('Use Quick Setup to generate a standard editable grading scale.')
            ->striped();
    }
}
