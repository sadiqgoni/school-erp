<?php

namespace App\Filament\Resources\TimetableEntries;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\TimetableEntries\Pages\CreateTimetableEntry;
use App\Filament\Resources\TimetableEntries\Pages\EditTimetableEntry;
use App\Filament\Resources\TimetableEntries\Pages\ListTimetableEntries;
use App\Filament\Resources\TimetableEntries\Schemas\TimetableEntryForm;
use App\Filament\Resources\TimetableEntries\Tables\TimetableEntriesTable;
use App\Models\TimetableEntry;
use App\Support\TeacherWorkspace;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TimetableEntryResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = TimetableEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Classroom';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Timetable';

    protected static ?string $modelLabel = 'timetable period';

    public static function getNavigationLabel(): string
    {
        return TeacherWorkspace::isTeacher() ? 'My Class Timetable' : static::$navigationLabel;
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return TeacherWorkspace::isTeacher() ? 'Teacher Workspace' : static::$navigationGroup;
    }

    public static function getNavigationSort(): ?int
    {
        return TeacherWorkspace::isTeacher() ? 30 : static::$navigationSort;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (TeacherWorkspace::isTeacher()) {
            TeacherWorkspace::applyFormAssignmentScope($query);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return TimetableEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TimetableEntriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTimetableEntries::route('/'),
            'create' => CreateTimetableEntry::route('/create'),
            'edit' => EditTimetableEntry::route('/{record}/edit'),
        ];
    }
}
