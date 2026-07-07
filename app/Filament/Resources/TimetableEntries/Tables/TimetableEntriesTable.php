<?php

namespace App\Filament\Resources\TimetableEntries\Tables;

use App\Models\TimetableEntry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TimetableEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['schoolClass', 'classSection', 'subject', 'staff']))
            ->columns([
                TextColumn::make('schoolClass.name')
                    ->label('Class')
                    ->state(fn (TimetableEntry $record): string => collect([
                        $record->schoolClass?->name,
                        $record->classSection?->name,
                    ])->filter()->join(' '))
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('day_of_week')
                    ->label('Day')
                    ->formatStateUsing(fn (TimetableEntry $record): string => $record->dayName())
                    ->sortable(),
                TextColumn::make('period_number')
                    ->label('Period')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Time')
                    ->state(fn (TimetableEntry $record): string => $record->timeRange() ?: '—'),
                TextColumn::make('subject.name')
                    ->label('Subject / Activity')
                    ->state(fn (TimetableEntry $record): string => $record->displayLabel())
                    ->weight('semibold')
                    ->color(fn (TimetableEntry $record): string => $record->entry_type === TimetableEntry::TYPE_BREAK ? 'warning' : 'primary'),
            ])
            ->groups([
                Group::make('day_of_week')
                    ->label('Day')
                    ->getTitleFromRecordUsing(fn (TimetableEntry $record): string => $record->dayName()),
            ])
            ->defaultGroup('day_of_week')
            ->filters([
                SelectFilter::make('school_class_id')
                    ->label('Class')
                    ->relationship('schoolClass', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('day_of_week')
                    ->label('Day')
                    ->options(TimetableEntry::DAYS),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('period_number')
            ->emptyStateHeading('No timetable yet')
            ->emptyStateDescription('Add periods one by one — the weekly grid builds itself for parents.')
            ->striped();
    }
}
