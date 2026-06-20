<?php

namespace App\Filament\Resources\FeePayments;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\FeePayments\Pages\CreateFeePayment;
use App\Filament\Resources\FeePayments\Pages\EditFeePayment;
use App\Filament\Resources\FeePayments\Pages\ListFeePayments;
use App\Filament\Resources\FeePayments\Schemas\FeePaymentForm;
use App\Filament\Resources\FeePayments\Tables\FeePaymentsTable;
use App\Models\FeePayment;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FeePaymentResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = FeePayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Payments';

    protected static string|\UnitEnum|null $navigationGroup = 'Billing & Payments';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return FeePaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeePaymentsTable::configure($table);
    }

    public static function getNavigationBadge(): ?string
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return null;
        }

        $count = FeePayment::query()
            ->where('school_id', $tenant->getKey())
            ->where('status', 'confirmed')
            ->whereNull('acknowledged_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'New confirmed payments awaiting acknowledgement';
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
            'index' => ListFeePayments::route('/'),
            'create' => CreateFeePayment::route('/create'),
            'edit' => EditFeePayment::route('/{record}/edit'),
        ];
    }
}
