<?php

namespace App\Filament\Resources\UserActivities\Tables;

use App\Models\UserActivity;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['user', 'school']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'login' => 'success',
                        'logout' => 'gray',
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'page_view' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->placeholder('System')
                    ->description(fn (UserActivity $record): ?string => $record->user?->email),
                TextColumn::make('school.name')
                    ->label('School')
                    ->searchable()
                    ->placeholder('Platform')
                    ->badge()
                    ->color('info'),
                TextColumn::make('description')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('auditable_type')
                    ->label('Record')
                    ->formatStateUsing(fn (?string $state, UserActivity $record): string => $state
                        ? class_basename($state).' #'.$record->auditable_id
                        : '-')
                    ->toggleable(),
                TextColumn::make('panel')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('url')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options([
                        'login' => 'Login',
                        'logout' => 'Logout',
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'page_view' => 'Page view',
                    ]),
                SelectFilter::make('school_id')
                    ->label('School')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('panel')
                    ->options([
                        'admin' => 'Admin',
                        'school' => 'School',
                    ]),
            ])
            ->recordActions([])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No activity recorded yet')
            ->emptyStateDescription('Every create, update, delete, and login across the platform will appear here.')
            ->striped();
    }
}
