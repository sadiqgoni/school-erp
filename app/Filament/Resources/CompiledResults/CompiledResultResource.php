<?php

namespace App\Filament\Resources\CompiledResults;

use App\Filament\Resources\CompiledResults\Pages\CreateCompiledResult;
use App\Filament\Resources\CompiledResults\Pages\EditCompiledResult;
use App\Filament\Resources\CompiledResults\Pages\ListCompiledResults;
use App\Filament\Resources\CompiledResults\Schemas\CompiledResultForm;
use App\Filament\Resources\CompiledResults\Tables\CompiledResultsTable;
use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Models\CompiledResult;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompiledResultResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = CompiledResult::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Exams & Reports';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Compile Results';

    public static function form(Schema $schema): Schema
    {
        return CompiledResultForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompiledResultsTable::configure($table);
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
            'index' => ListCompiledResults::route('/'),
            'create' => CreateCompiledResult::route('/create'),
            'edit' => EditCompiledResult::route('/{record}/edit'),
        ];
    }
}
