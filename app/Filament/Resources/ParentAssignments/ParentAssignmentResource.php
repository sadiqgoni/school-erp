<?php

namespace App\Filament\Resources\ParentAssignments;

use App\Filament\Resources\ParentAssignments\Pages\ListParentAssignments;
use App\Filament\Resources\ParentAssignments\Tables\ParentAssignmentsTable;
use App\Models\Assignment;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ParentAssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Homework';

    protected static string|\UnitEnum|null $navigationGroup = 'Academics & Fees';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'homework';

    public static function canAccess(): bool
    {
        return static::isParentForTenant() && parent::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::isParentForTenant() && parent::shouldRegisterNavigation();
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::isParentForTenant()) {
            return null;
        }

        $pending = ParentAssignmentsTable::pendingCountForParent();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Homework not yet confirmed as done';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return ParentAssignmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParentAssignments::route('/'),
        ];
    }

    protected static function isParentForTenant(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'school'
            && (bool) Filament::auth()->user()?->hasSchoolRole(Filament::getTenant(), 'parent');
    }
}
