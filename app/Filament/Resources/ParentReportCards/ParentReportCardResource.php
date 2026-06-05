<?php

namespace App\Filament\Resources\ParentReportCards;

use App\Filament\Resources\ParentReportCards\Pages\ListParentReportCards;
use App\Filament\Resources\ParentReportCards\Tables\ParentReportCardsTable;
use App\Models\ReportCard;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ParentReportCardResource extends Resource
{
    protected static ?string $model = ReportCard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'My Results';

    protected static string|\UnitEnum|null $navigationGroup = 'Parent Portal';

    protected static ?int $navigationSort = 3;

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
        return ParentReportCardsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParentReportCards::route('/'),
        ];
    }

    protected static function isParentForTenant(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'school'
            && (bool) Filament::auth()->user()?->hasSchoolRole(Filament::getTenant(), 'parent');
    }
}
