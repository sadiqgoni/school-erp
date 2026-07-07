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
use Illuminate\Database\Eloquent\Model;

class ParentInvoiceResource extends Resource
{
    protected static ?string $model = StudentInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'My Invoices';

    protected static string|\UnitEnum|null $navigationGroup = 'Academics & Fees';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function canAccess(): bool
    {
        return static::isParentForTenant() && parent::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::isParentForTenant() && parent::shouldRegisterNavigation();
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::isParentForTenant()) {
            return null;
        }

        $count = StudentInvoice::query()
            ->where('school_id', Filament::getTenant()?->getKey())
            ->where('balance', '>', 0)
            ->whereNot('status', 'cancelled')
            ->whereHas('student.guardianLinks.guardian', fn ($query) => $query->where('user_id', Filament::auth()->id()))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Unpaid school fee invoices';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['invoice_number', 'student.first_name', 'student.middle_name', 'student.last_name', 'status'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->invoice_number;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter([
            'Student' => $record->student?->full_name,
            'Status' => $record->status,
            'Balance' => 'NGN ' . number_format((float) $record->balance, 2),
        ]);
    }

    public static function getGlobalSearchResultUrl(Model $record): ?string
    {
        return static::getUrl('index', ['tableSearch' => $record->invoice_number]);
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
