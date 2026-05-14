<?php

namespace App\Filament\Resources\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

trait SchoolPanelResource
{
    protected static array $teacherResources = [
        'App\\Filament\\Resources\\ClassSubjects\\ClassSubjectResource',
        'App\\Filament\\Resources\\ReportCards\\ReportCardResource',
        'App\\Filament\\Resources\\StudentScores\\StudentScoreResource',
    ];

    public static function canAccess(): bool
    {
        return static::canAccessSchoolResource() && parent::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (! static::isSchoolPanel() || ! parent::shouldRegisterNavigation()) {
            return false;
        }

        return ! static::isTeacherUser() || static::isTeacherResource();
    }

    public static function canViewAny(): bool
    {
        return static::canAccessSchoolResource() && parent::canViewAny();
    }

    public static function canCreate(): bool
    {
        if (! static::canAccessSchoolResource()) {
            return false;
        }

        return ! static::isTeacherUser() && parent::canCreate();
    }

    public static function canView(Model $record): bool
    {
        return static::canAccessSchoolResource() && parent::canView($record);
    }

    public static function canEdit(Model $record): bool
    {
        if (! static::canAccessSchoolResource()) {
            return false;
        }

        return ! static::isTeacherUser() && parent::canEdit($record);
    }

    protected static function isSchoolPanel(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'school';
    }

    protected static function canAccessSchoolResource(): bool
    {
        if (! static::isSchoolPanel()) {
            return false;
        }

        return ! static::isTeacherUser() || static::isTeacherResource();
    }

    protected static function isTeacherUser(): bool
    {
        $user = Filament::auth()->user();

        return (bool) $user?->hasSchoolRole(Filament::getTenant(), 'teacher');
    }

    protected static function isTeacherResource(): bool
    {
        return in_array(static::class, static::$teacherResources, true);
    }
}
