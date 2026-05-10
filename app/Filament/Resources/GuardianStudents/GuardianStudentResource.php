<?php

namespace App\Filament\Resources\GuardianStudents;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\GuardianStudents\Pages\CreateGuardianStudent;
use App\Filament\Resources\GuardianStudents\Pages\EditGuardianStudent;
use App\Filament\Resources\GuardianStudents\Pages\ListGuardianStudents;
use App\Filament\Resources\GuardianStudents\Schemas\GuardianStudentForm;
use App\Filament\Resources\GuardianStudents\Tables\GuardianStudentsTable;
use App\Models\GuardianStudent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GuardianStudentResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = GuardianStudent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|\UnitEnum|null $navigationGroup = 'Students';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Guardian Links';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return GuardianStudentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GuardianStudentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGuardianStudents::route('/'),
            'create' => CreateGuardianStudent::route('/create'),
            'edit' => EditGuardianStudent::route('/{record}/edit'),
        ];
    }
}
