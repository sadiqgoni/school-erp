<?php

namespace App\Filament\Resources\StaffRoles;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\StaffRoles\Pages\CreateStaffRole;
use App\Filament\Resources\StaffRoles\Pages\EditStaffRole;
use App\Filament\Resources\StaffRoles\Pages\ListStaffRoles;
use App\Filament\Resources\StaffRoles\Schemas\StaffRoleForm;
use App\Filament\Resources\StaffRoles\Tables\StaffRolesTable;
use App\Models\StaffRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StaffRoleResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = StaffRole::class;

    protected static ?string $navigationLabel = 'Staff Roles';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'Staff';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return StaffRoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffRolesTable::configure($table);
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
            'index' => ListStaffRoles::route('/'),
            'create' => CreateStaffRole::route('/create'),
            'edit' => EditStaffRole::route('/{record}/edit'),
        ];
    }
}
