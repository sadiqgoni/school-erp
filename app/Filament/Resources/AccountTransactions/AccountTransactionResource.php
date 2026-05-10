<?php

namespace App\Filament\Resources\AccountTransactions;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\AccountTransactions\Pages\CreateAccountTransaction;
use App\Filament\Resources\AccountTransactions\Pages\EditAccountTransaction;
use App\Filament\Resources\AccountTransactions\Pages\ListAccountTransactions;
use App\Filament\Resources\AccountTransactions\Schemas\AccountTransactionForm;
use App\Filament\Resources\AccountTransactions\Tables\AccountTransactionsTable;
use App\Models\AccountTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AccountTransactionResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = AccountTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Transactions';

    protected static string|\UnitEnum|null $navigationGroup = 'Accounts';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return AccountTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountTransactionsTable::configure($table);
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
            'index' => ListAccountTransactions::route('/'),
            'create' => CreateAccountTransaction::route('/create'),
            'edit' => EditAccountTransaction::route('/{record}/edit'),
        ];
    }
}
