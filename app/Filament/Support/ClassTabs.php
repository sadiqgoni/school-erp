<?php

namespace App\Filament\Support;

use App\Models\SchoolClass;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Tabs\Tab;

class ClassTabs
{
    public static function direct(string $modelClass, string $allLabel = 'All classes', string $column = 'school_class_id'): array
    {
        return self::make($allLabel, fn (SchoolClass $class): Tab => Tab::make($class->name)
            ->badge(fn (): int => $modelClass::query()
                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                ->where($column, $class->getKey())
                ->count())
            ->modifyQueryUsing(fn ($query) => $query->where($column, $class->getKey())));
    }

    public static function studentEnrollment(string $modelClass, string $allLabel = 'All classes', string $relationship = 'student.enrollments'): array
    {
        return self::make($allLabel, fn (SchoolClass $class): Tab => Tab::make($class->name)
            ->badge(fn (): int => $modelClass::query()
                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                ->whereHas($relationship, fn ($query) => $query->where('school_class_id', $class->getKey()))
                ->count())
            ->modifyQueryUsing(fn ($query) => $query->whereHas(
                $relationship,
                fn ($query) => $query->where('school_class_id', $class->getKey()),
            )));
    }

    public static function directOrStudentEnrollment(
        string $modelClass,
        string $allLabel = 'All classes',
        string $column = 'school_class_id',
        string $relationship = 'student.enrollments',
    ): array {
        return self::make($allLabel, fn (SchoolClass $class): Tab => Tab::make($class->name)
            ->badge(fn (): int => $modelClass::query()
                ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
                ->where(function ($query) use ($column, $relationship, $class): void {
                    $query
                        ->where($column, $class->getKey())
                        ->orWhereHas($relationship, fn ($query) => $query->where('school_class_id', $class->getKey()));
                })
                ->count())
            ->modifyQueryUsing(fn ($query) => $query->where(function ($query) use ($column, $relationship, $class): void {
                $query
                    ->where($column, $class->getKey())
                    ->orWhereHas($relationship, fn ($query) => $query->where('school_class_id', $class->getKey()));
            })));
    }

    protected static function make(string $allLabel, callable $tabFactory): array
    {
        $tabs = [
            'all' => Tab::make($allLabel),
        ];

        self::classes()->each(function (SchoolClass $class) use (&$tabs, $tabFactory): void {
            $tabs['class_'.$class->getKey()] = $tabFactory($class);
        });

        return $tabs;
    }

    protected static function classes()
    {
        return SchoolClass::query()
            ->when(Filament::getTenant(), fn ($query, $tenant) => $query->where('school_id', $tenant->getKey()))
            ->where('is_active', true)
            ->orderBy('level')
            ->orderBy('name')
            ->get();
    }
}
