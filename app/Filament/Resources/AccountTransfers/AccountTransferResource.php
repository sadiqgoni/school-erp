<?php

namespace App\Filament\Resources\AccountTransfers;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\AccountTransfers\Pages\CreateAccountTransfer;
use App\Filament\Resources\AccountTransfers\Pages\EditAccountTransfer;
use App\Filament\Resources\AccountTransfers\Pages\ListAccountTransfers;
use App\Filament\Resources\AccountTransfers\Schemas\AccountTransferForm;
use App\Filament\Resources\AccountTransfers\Tables\AccountTransfersTable;
use App\Models\AccountTransfer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AccountTransferResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = AccountTransfer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Transfers';

    protected static string|\UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return AccountTransferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountTransfersTable::configure($table);
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
            'index' => ListAccountTransfers::route('/'),
            'create' => CreateAccountTransfer::route('/create'),
            'edit' => EditAccountTransfer::route('/{record}/edit'),
        ];
    }
}
