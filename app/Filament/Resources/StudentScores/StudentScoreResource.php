<?php

namespace App\Filament\Resources\StudentScores;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\StudentScores\Pages\CreateStudentScore;
use App\Filament\Resources\StudentScores\Pages\EditStudentScore;
use App\Filament\Resources\StudentScores\Pages\ListStudentScores;
use App\Filament\Resources\StudentScores\Schemas\StudentScoreForm;
use App\Filament\Resources\StudentScores\Tables\StudentScoresTable;
use App\Models\StudentScore;
use App\Support\TeacherWorkspace;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StudentScoreResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = StudentScore::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static string|\UnitEnum|null $navigationGroup = 'Exams & Reports';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'Score Entry';

    public static function getNavigationLabel(): string
    {
        return TeacherWorkspace::isTeacher() ? 'My Score Entry' : static::$navigationLabel;
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return TeacherWorkspace::isTeacher() ? 'Teacher Portal' : static::$navigationGroup;
    }

    public static function form(Schema $schema): Schema
    {
        return StudentScoreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentScoresTable::configure($table);
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
            'index' => ListStudentScores::route('/'),
            'create' => CreateStudentScore::route('/create'),
            'edit' => EditStudentScore::route('/{record}/edit'),
        ];
    }
}
