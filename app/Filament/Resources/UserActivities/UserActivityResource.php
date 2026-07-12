<?php

namespace App\Filament\Resources\UserActivities;

use App\Filament\Resources\UserActivities\Pages\ListUserActivities;
use App\Filament\Resources\UserActivities\Tables\UserActivitiesTable;
use App\Models\UserActivity;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserActivityResource extends Resource
{
    protected static ?string $model = UserActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Audit Trail';

    protected static ?string $modelLabel = 'activity';

    protected static ?string $pluralModelLabel = 'activity log';

    protected static ?int $navigationSort = 30;

    public static function canAccess(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Monitoring';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return UserActivitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserActivities::route('/'),
        ];
    }
}
