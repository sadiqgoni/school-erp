<?php

namespace App\Filament\Resources\StaffBanks;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\StaffBanks\Pages\CreateStaffBank;
use App\Filament\Resources\StaffBanks\Pages\EditStaffBank;
use App\Filament\Resources\StaffBanks\Pages\ListStaffBanks;
use App\Filament\Resources\StaffBanks\Schemas\StaffBankForm;
use App\Filament\Resources\StaffBanks\Tables\StaffBanksTable;
use App\Models\StaffBank;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StaffBankResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = StaffBank::class;

    protected static ?string $navigationLabel = 'Staff Banks';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance Setup';

    protected static ?int $navigationSort = 45;

    public static function form(Schema $schema): Schema
    {
        return StaffBankForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffBanksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaffBanks::route('/'),
            'create' => CreateStaffBank::route('/create'),
            'edit' => EditStaffBank::route('/{record}/edit'),
        ];
    }
}
