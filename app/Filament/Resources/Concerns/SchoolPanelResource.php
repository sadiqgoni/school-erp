<?php

namespace App\Filament\Resources\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

trait SchoolPanelResource
{
    public static function canAccess(): bool
    {
        return static::isSchoolPanel() && parent::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::isSchoolPanel() && parent::shouldRegisterNavigation();
    }

    public static function canViewAny(): bool
    {
        return static::isSchoolPanel() && parent::canViewAny();
    }

    public static function canCreate(): bool
    {
        return static::isSchoolPanel() && parent::canCreate();
    }

    public static function canView(Model $record): bool
    {
        return static::isSchoolPanel() && parent::canView($record);
    }

    public static function canEdit(Model $record): bool
    {
        return static::isSchoolPanel() && parent::canEdit($record);
    }

    protected static function isSchoolPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'school';
    }
}
