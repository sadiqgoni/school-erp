<?php

namespace App\Filament\Resources\StudentMovements;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\StudentMovements\Pages\ListStudentMovements;
use App\Models\StudentMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentMovementResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = StudentMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Safety & Transport';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Arrivals & Exits';

    protected static ?string $modelLabel = 'movement';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['student', 'device', 'busRoute', 'recordedBy']))
            ->columns([
                TextColumn::make('happened_at')
                    ->label('Time')
                    ->dateTime('d M Y · h:i A')
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable()
                    ->description(fn (StudentMovement $record): ?string => $record->student?->admission_number),
                TextColumn::make('event_type')
                    ->label('Event')
                    ->state(fn (StudentMovement $record): string => $record->eventLabel())
                    ->badge()
                    ->color(fn (StudentMovement $record): string => match ($record->event_type) {
                        StudentMovement::EVENT_CHECK_IN => 'success',
                        StudentMovement::EVENT_CHECK_OUT => 'warning',
                        StudentMovement::EVENT_BUS_BOARDED => 'info',
                        StudentMovement::EVENT_BUS_DROPPED => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('busRoute.name')
                    ->label('Bus route')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('source')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'device' => 'Card scan',
                        'manual' => 'Recorded by staff',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => $state === 'device' ? 'info' : 'gray'),
                TextColumn::make('recordedBy.name')
                    ->label('Recorded by')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')
                    ->placeholder('—')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Event')
                    ->options(StudentMovement::EVENTS),
                SelectFilter::make('bus_route_id')
                    ->label('Bus route')
                    ->relationship('busRoute', 'name'),
                SelectFilter::make('student_id')
                    ->label('Student')
                    ->relationship('student', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->full_name)
                    ->searchable(),
            ])
            ->defaultSort('happened_at', 'desc')
            ->emptyStateHeading('No movements recorded yet')
            ->emptyStateDescription('Gate scans and manual check-ins will appear here, and parents see them instantly.')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudentMovements::route('/'),
        ];
    }
}
