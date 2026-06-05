<?php

namespace App\Filament\Resources\FeeStructures\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FeeStructuresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                TextColumn::make('academicYear.name')->label('Academic year')->sortable(),
                TextColumn::make('schoolClass.name')->label('Class')->sortable(),
                TextColumn::make('feeType.name')->label('Fee')->searchable()->sortable(),
                TextColumn::make('amount')->money('NGN')->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() === 'admin'),
                SelectFilter::make('academic_year_id')->label('Academic year')->relationship('academicYear', 'name')->searchable()->preload(),
                SelectFilter::make('school_class_id')->label('Class')->relationship('schoolClass', 'name')->searchable()->preload(),
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
