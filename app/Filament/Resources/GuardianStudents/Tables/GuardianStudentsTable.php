<?php

namespace App\Filament\Resources\GuardianStudents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GuardianStudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('guardian.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.admission_number')
                    ->label('Admission no.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.last_name')
                    ->label('Student surname')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('relationship')
                    ->badge(),
                IconColumn::make('is_primary_contact')
                    ->boolean(),
                IconColumn::make('receives_sms')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('school')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('relationship')
                    ->options([
                        'father' => 'Father',
                        'mother' => 'Mother',
                        'guardian' => 'Guardian',
                        'uncle' => 'Uncle',
                        'aunt' => 'Aunt',
                        'sibling' => 'Sibling',
                        'other' => 'Other',
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
