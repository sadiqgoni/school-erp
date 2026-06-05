<?php

namespace App\Filament\Resources\ParentInvoices;

use App\Filament\Resources\ParentInvoices\Pages\ListParentInvoices;
use App\Filament\Resources\ParentInvoices\Tables\ParentInvoicesTable;
use App\Models\StudentInvoice;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ParentInvoiceResource extends Resource
{
    protected static ?string $model = StudentInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'My Invoices';

    protected static string|\UnitEnum|null $navigationGroup = 'Parent Portal';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return static::isParentForTenant() && parent::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::isParentForTenant() && parent::shouldRegisterNavigation();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return ParentInvoicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParentInvoices::route('/'),
        ];
    }

    protected static function isParentForTenant(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'school'
            && (bool) Filament::auth()->user()?->hasSchoolRole(Filament::getTenant(), 'parent');
    }
}
