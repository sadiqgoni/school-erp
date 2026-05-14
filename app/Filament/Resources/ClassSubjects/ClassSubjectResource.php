<?php

namespace App\Filament\Resources\ClassSubjects;

use App\Filament\Resources\ClassSubjects\Pages\CreateClassSubject;
use App\Filament\Resources\ClassSubjects\Pages\EditClassSubject;
use App\Filament\Resources\ClassSubjects\Pages\ListClassSubjects;
use App\Filament\Resources\ClassSubjects\Schemas\ClassSubjectForm;
use App\Filament\Resources\ClassSubjects\Tables\ClassSubjectsTable;
use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Models\ClassSubject;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClassSubjectResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = ClassSubject::class;

    protected static ?string $navigationLabel = 'Subject Setup';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'School Setup';

    protected static ?int $navigationSort = 60;

    public static function getNavigationLabel(): string
    {
        return Filament::auth()->user()?->hasSchoolRole(Filament::getTenant(), 'teacher')
            ? 'My Class Subjects'
            : static::$navigationLabel;
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return Filament::auth()->user()?->hasSchoolRole(Filament::getTenant(), 'teacher')
            ? 'Teacher Portal'
            : static::$navigationGroup;
    }

    public static function form(Schema $schema): Schema
    {
        return ClassSubjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClassSubjectsTable::configure($table);
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
            'index' => ListClassSubjects::route('/'),
            'create' => CreateClassSubject::route('/create'),
            'edit' => EditClassSubject::route('/{record}/edit'),
        ];
    }
}
