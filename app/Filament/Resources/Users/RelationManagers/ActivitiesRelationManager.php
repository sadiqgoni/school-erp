<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\UserActivity;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Audit activity';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
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
                TextColumn::make('description')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('school.name')
                    ->label('School')
                    ->placeholder('Platform')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('auditable_type')
                    ->label('Record')
                    ->formatStateUsing(fn (?string $state, UserActivity $record): string => $state
                        ? class_basename($state).' #'.$record->auditable_id
                        : '-')
                    ->toggleable(),
                TextColumn::make('old_values')
                    ->label('Before')
                    ->formatStateUsing(fn (mixed $state): string => self::formatValues($state))
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('new_values')
                    ->label('After')
                    ->formatStateUsing(fn (mixed $state): string => self::formatValues($state))
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('panel')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected static function formatValues(mixed $values): string
    {
        if (is_string($values)) {
            $decodedValues = json_decode($values, true);
            $values = json_last_error() === JSON_ERROR_NONE ? $decodedValues : $values;
        }

        if (blank($values)) {
            return '-';
        }

        if (! is_array($values)) {
            return self::stringValue($values);
        }

        return collect($values)
            ->map(fn ($value, string|int $key): string => $key.': '.self::stringValue($value))
            ->join("\n");
    }

    protected static function stringValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES) ?: '';
        }

        return (string) $value;
    }
}
