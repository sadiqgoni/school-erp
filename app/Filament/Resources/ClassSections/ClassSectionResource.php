<?php

namespace App\Filament\Resources\ClassSections;

use App\Filament\Resources\ClassSections\Pages\CreateClassSection;
use App\Filament\Resources\ClassSections\Pages\EditClassSection;
use App\Filament\Resources\ClassSections\Pages\ListClassSections;
use App\Filament\Resources\ClassSections\RelationManagers\FormTeachersRelationManager;
use App\Filament\Resources\ClassSections\RelationManagers\StudentsRelationManager;
use App\Filament\Resources\ClassSections\Schemas\ClassSectionForm;
use App\Filament\Resources\ClassSections\Tables\ClassSectionsTable;
use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Models\ClassSection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClassSectionResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = ClassSection::class;

    protected static ?string $modelLabel = 'Arm';

    protected static ?string $pluralModelLabel = 'Arms';

    protected static ?string $navigationLabel = 'Arms';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|\UnitEnum|null $navigationGroup = 'School Setup';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return ClassSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClassSectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FormTeachersRelationManager::class,
            StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClassSections::route('/'),
            'create' => CreateClassSection::route('/create'),
            'edit' => EditClassSection::route('/{record}/edit'),
        ];
    }
}
