<?php

namespace App\Filament\Resources\ResultTraitItems;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\ResultTraitItems\Pages\CreateResultTraitItem;
use App\Filament\Resources\ResultTraitItems\Pages\EditResultTraitItem;
use App\Filament\Resources\ResultTraitItems\Pages\ListResultTraitItems;
use App\Filament\Resources\ResultTraitItems\Schemas\ResultTraitItemForm;
use App\Filament\Resources\ResultTraitItems\Tables\ResultTraitItemsTable;
use App\Models\ResultTraitItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ResultTraitItemResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = ResultTraitItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|\UnitEnum|null $navigationGroup = 'Exams & Reports';

    protected static ?int $navigationSort = 35;

    protected static ?string $navigationLabel = 'Result Traits';

    public static function form(Schema $schema): Schema
    {
        return ResultTraitItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResultTraitItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResultTraitItems::route('/'),
            'create' => CreateResultTraitItem::route('/create'),
            'edit' => EditResultTraitItem::route('/{record}/edit'),
        ];
    }
}
