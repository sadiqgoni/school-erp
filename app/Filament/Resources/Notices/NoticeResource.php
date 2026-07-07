<?php

namespace App\Filament\Resources\Notices;

use App\Filament\Resources\Concerns\SchoolPanelResource;
use App\Filament\Resources\Notices\Pages\CreateNotice;
use App\Filament\Resources\Notices\Pages\EditNotice;
use App\Filament\Resources\Notices\Pages\ListNotices;
use App\Filament\Resources\Notices\Schemas\NoticeForm;
use App\Filament\Resources\Notices\Tables\NoticesTable;
use App\Models\Notice;
use App\Support\TeacherWorkspace;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NoticeResource extends Resource
{
    use SchoolPanelResource;

    protected static ?string $model = Notice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|\UnitEnum|null $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Notices & Newsletters';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return TeacherWorkspace::isTeacher() ? 'Class Announcements' : static::$navigationLabel;
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return TeacherWorkspace::isTeacher() ? 'Teacher Workspace' : static::$navigationGroup;
    }

    public static function getNavigationSort(): ?int
    {
        return TeacherWorkspace::isTeacher() ? 90 : static::$navigationSort;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (TeacherWorkspace::isTeacher()) {
            $query->where(fn (Builder $query): Builder => $query
                ->where('created_by', Filament::auth()->id())
                ->orWhereIn('school_class_id', TeacherWorkspace::formClassIds() ?: [0]));
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return NoticeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NoticesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotices::route('/'),
            'create' => CreateNotice::route('/create'),
            'edit' => EditNotice::route('/{record}/edit'),
        ];
    }
}
