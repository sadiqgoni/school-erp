<?php

namespace App\Filament\Resources\Assignments;

use App\Filament\Resources\Assignments\Pages\CreateAssignment;
use App\Filament\Resources\Assignments\Pages\EditAssignment;
use App\Filament\Resources\Assignments\Pages\ListAssignments;
use App\Filament\Resources\Assignments\Schemas\AssignmentForm;
use App\Filament\Resources\Assignments\Tables\AssignmentsTable;
use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Models\Assignment;
use App\Support\TeacherWorkspace;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssignmentResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = Assignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Classroom';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Assignments';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return TeacherWorkspace::isTeacher() ? 'My Assignments' : static::$navigationLabel;
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return TeacherWorkspace::isTeacher() ? 'Teacher Workspace' : static::$navigationGroup;
    }

    public static function getNavigationSort(): ?int
    {
        return TeacherWorkspace::isTeacher() ? 60 : static::$navigationSort;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (TeacherWorkspace::isTeacher()) {
            $staff = TeacherWorkspace::currentStaff();
            $classIds = TeacherWorkspace::teachableClassIds();

            $query->where(fn (Builder $query): Builder => $query
                ->where('staff_id', $staff?->getKey())
                ->orWhereIn('school_class_id', $classIds ?: [0]));
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return AssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssignmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssignments::route('/'),
            'create' => CreateAssignment::route('/create'),
            'edit' => EditAssignment::route('/{record}/edit'),
        ];
    }
}
