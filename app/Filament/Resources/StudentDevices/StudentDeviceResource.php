<?php

namespace App\Filament\Resources\StudentDevices;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\StudentDevices\Pages\CreateStudentDevice;
use App\Filament\Resources\StudentDevices\Pages\EditStudentDevice;
use App\Filament\Resources\StudentDevices\Pages\ListStudentDevices;
use App\Filament\Support\SchoolSelect;
use App\Models\Student;
use App\Models\StudentDevice;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentDeviceResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = StudentDevice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static string|\UnitEnum|null $navigationGroup = 'Safety & Transport';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'NFC Cards & Watches';

    protected static ?string $modelLabel = 'student device';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Student device')
                    ->description('Register the NFC card, watch, or tag a student carries. Gate scans of this device update the parent portal.')
                    ->schema([
                        SchoolSelect::make(),
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
                            ->required(),
                        TextInput::make('identifier')
                            ->label('Card / device number')
                            ->helperText('The unique code the scanner reads (NFC UID or serial).')
                            ->required()
                            ->maxLength(120),
                        Select::make('device_type')
                            ->label('Type')
                            ->options(StudentDevice::TYPES)
                            ->default('nfc_card')
                            ->required(),
                        TextInput::make('label')
                            ->label('Label (optional)')
                            ->placeholder('e.g. Blue wristband')
                            ->maxLength(120),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('student'))
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('Student')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn (StudentDevice $record): ?string => $record->student?->admission_number),
                TextColumn::make('identifier')
                    ->label('Device number')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('device_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StudentDevice::TYPES[$state] ?? $state)
                    ->color('info'),
                TextColumn::make('label')
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->date('d M Y')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('device_type')
                    ->label('Type')
                    ->options(StudentDevice::TYPES),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No devices registered')
            ->emptyStateDescription('Register an NFC card or watch for a student to start tracking arrivals and exits.')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudentDevices::route('/'),
            'create' => CreateStudentDevice::route('/create'),
            'edit' => EditStudentDevice::route('/{record}/edit'),
        ];
    }
}
