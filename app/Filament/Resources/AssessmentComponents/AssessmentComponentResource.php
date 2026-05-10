<?php

namespace App\Filament\Resources\AssessmentComponents;

use App\Filament\Resources\AssessmentComponents\Pages\CreateAssessmentComponent;
use App\Filament\Resources\AssessmentComponents\Pages\EditAssessmentComponent;
use App\Filament\Resources\AssessmentComponents\Pages\ListAssessmentComponents;
use App\Filament\Resources\AssessmentComponents\Schemas\AssessmentComponentForm;
use App\Filament\Resources\AssessmentComponents\Tables\AssessmentComponentsTable;
use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Models\AssessmentComponent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssessmentComponentResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = AssessmentComponent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Exams & Reports';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Assessment Setup';

    public static function form(Schema $schema): Schema
    {
        return AssessmentComponentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssessmentComponentsTable::configure($table);
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
            'index' => ListAssessmentComponents::route('/'),
            'create' => CreateAssessmentComponent::route('/create'),
            'edit' => EditAssessmentComponent::route('/{record}/edit'),
        ];
    }
}
