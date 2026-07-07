<?php

namespace App\Filament\Resources\BusRoutes;

use App\Filament\Resources\BusRoutes\Pages\CreateBusRoute;
use App\Filament\Resources\BusRoutes\Pages\EditBusRoute;
use App\Filament\Resources\BusRoutes\Pages\ListBusRoutes;
use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Support\SchoolSelect;
use App\Models\BusRoute;
use App\Models\Student;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BusRouteResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = BusRoute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Safety & Transport';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'School Bus Routes';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bus route')
                    ->schema([
                        SchoolSelect::make(),
                        TextInput::make('name')
                            ->label('Route name')
                            ->placeholder('e.g. GRA / Nassarawa route')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->label('Route code (optional)')
                            ->helperText('Used by bus scanners to identify the route.')
                            ->maxLength(40),
                        TextInput::make('vehicle_name')
                            ->label('Vehicle')
                            ->placeholder('e.g. Toyota Hiace — white')
                            ->maxLength(255),
                        TextInput::make('plate_number')
                            ->label('Plate number')
                            ->maxLength(40),
                        TextInput::make('driver_name')
                            ->label('Driver')
                            ->maxLength(255),
                        TextInput::make('driver_phone')
                            ->label('Driver phone')
                            ->tel()
                            ->maxLength(40),
                        TextInput::make('assistant_name')
                            ->label('Bus assistant (optional)')
                            ->maxLength(255),
                        TextInput::make('assistant_phone')
                            ->label('Assistant phone')
                            ->tel()
                            ->maxLength(40),
                        TextInput::make('capacity')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(200),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Students on this route')
                    ->description('Add the students who ride this bus and their pickup points.')
                    ->schema([
                        Repeater::make('studentAssignments')
                            ->relationship()
                            ->hiddenLabel()
                            ->schema([
                                Select::make('student_id')
                                    ->label('Student')
                                    ->options(fn (): array => Student::query()
                                        ->where('status', 'active')
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn (Student $student): array => [
                                            $student->getKey() => $student->full_name.' ('.$student->admission_number.')',
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->required()
                                    ->distinct(),
                                TextInput::make('pickup_point')
                                    ->label('Pickup point')
                                    ->maxLength(255),
                                TextInput::make('drop_point')
                                    ->label('Drop-off point')
                                    ->maxLength(255),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add student')
                            ->defaultItems(0)
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, BusRoute $record): array {
                                $data['school_id'] = $record->school_id;

                                return $data;
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('studentAssignments'))
            ->columns([
                TextColumn::make('name')
                    ->label('Route')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn (BusRoute $record): ?string => $record->vehicle_name),
                TextColumn::make('driver_name')
                    ->label('Driver')
                    ->placeholder('—')
                    ->description(fn (BusRoute $record): ?string => $record->driver_phone),
                TextColumn::make('plate_number')
                    ->label('Plate')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('student_assignments_count')
                    ->label('Students')
                    ->badge()
                    ->color('info'),
                TextColumn::make('capacity')
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No bus routes yet')
            ->emptyStateDescription('Create a route, add the driver and the students who ride it.')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusRoutes::route('/'),
            'create' => CreateBusRoute::route('/create'),
            'edit' => EditBusRoute::route('/{record}/edit'),
        ];
    }
}
