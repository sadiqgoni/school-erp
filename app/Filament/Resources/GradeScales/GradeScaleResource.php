<?php

namespace App\Filament\Resources\GradeScales;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\GradeScales\Pages\CreateGradeScale;
use App\Filament\Resources\GradeScales\Pages\EditGradeScale;
use App\Filament\Resources\GradeScales\Pages\ListGradeScales;
use App\Filament\Resources\GradeScales\Schemas\GradeScaleForm;
use App\Filament\Resources\GradeScales\Tables\GradeScalesTable;
use App\Models\GradeScale;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GradeScaleResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = GradeScale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|\UnitEnum|null $navigationGroup = 'Exams & Reports';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return GradeScaleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GradeScalesTable::configure($table);
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
            'index' => ListGradeScales::route('/'),
            'create' => CreateGradeScale::route('/create'),
            'edit' => EditGradeScale::route('/{record}/edit'),
        ];
    }
}
