<?php

namespace App\Filament\Resources\TeachingAssignments;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\TeachingAssignments\Pages\CreateTeachingAssignment;
use App\Filament\Resources\TeachingAssignments\Pages\EditTeachingAssignment;
use App\Filament\Resources\TeachingAssignments\Pages\ListTeachingAssignments;
use App\Filament\Resources\TeachingAssignments\Schemas\TeachingAssignmentForm;
use App\Filament\Resources\TeachingAssignments\Tables\TeachingAssignmentsTable;
use App\Models\TeachingAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeachingAssignmentResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = TeachingAssignment::class;

    protected static ?string $navigationLabel = 'Teacher Assignments';

    protected static ?string $modelLabel = 'Teacher Assignment';

    protected static ?string $pluralModelLabel = 'Teacher Assignments';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Staff';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return TeachingAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeachingAssignmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('assignment_role', [
                TeachingAssignment::ROLE_FORM_TEACHER,
                TeachingAssignment::ROLE_ASSISTANT_FORM_TEACHER,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeachingAssignments::route('/'),
            'create' => CreateTeachingAssignment::route('/create'),
            'edit' => EditTeachingAssignment::route('/{record}/edit'),
        ];
    }
}
