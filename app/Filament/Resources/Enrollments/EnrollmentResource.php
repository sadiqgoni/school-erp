<?php

namespace App\Filament\Resources\Enrollments;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\Enrollments\Pages\CreateEnrollment;
use App\Filament\Resources\Enrollments\Pages\EditEnrollment;
use App\Filament\Resources\Enrollments\Pages\ListEnrollments;
use App\Filament\Resources\Enrollments\Schemas\EnrollmentForm;
use App\Filament\Resources\Enrollments\Tables\EnrollmentsTable;
use App\Models\Enrollment;
use App\Support\TeacherWorkspace;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = Enrollment::class;

    protected static ?string $navigationLabel = 'Class Placements';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Students';

    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return TeacherWorkspace::isTeacher() ? 'Promotion & Placements' : static::$navigationLabel;
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return TeacherWorkspace::isTeacher() ? 'Class Management' : static::$navigationGroup;
    }

    public static function getNavigationSort(): ?int
    {
        return TeacherWorkspace::isTeacher() ? 10 : static::$navigationSort;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (TeacherWorkspace::isTeacher()) {
            $query->whereIn('school_class_id', TeacherWorkspace::formClassIds() ?: [0]);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return EnrollmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EnrollmentsTable::configure($table);
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
            'index' => ListEnrollments::route('/'),
            'create' => CreateEnrollment::route('/create'),
            'edit' => EditEnrollment::route('/{record}/edit'),
        ];
    }
}
