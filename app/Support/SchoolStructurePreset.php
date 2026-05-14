<?php

namespace App\Support;

class SchoolStructurePreset
{
    public static function defaultTemplatesForDivision(?string $division): array
    {
        return match ($division) {
            'nursery' => ['nursery', 'kindergarten'],
            'primary' => ['primary'],
            'secondary' => ['secondary'],
            default => [],
        };
    }

    public static function options(): array
    {
        return [
            'nursery' => 'Nursery',
            'kindergarten' => 'Kindergarten',
            'primary' => 'Primary',
            'secondary' => 'Secondary',
            'basic' => 'Basic',
            'grade' => 'Grade',
            'cambridge' => 'Cambridge',
        ];
    }

    public static function optionsForDivision(?string $division): array
    {
        $templates = static::defaultTemplatesForDivision($division);

        return $templates === []
            ? static::options()
            : collect(static::options())->only($templates)->all();
    }

    public static function defaults(array $templates): array
    {
        $classes = collect($templates)
            ->flatMap(fn (string $template): array => static::template($template))
            ->unique('code')
            ->values()
            ->all();

        return array_map(function (array $class): array {
            return $class + [
                'description' => null,
                'is_active' => true,
            ];
        }, $classes);
    }

    protected static function template(string $template): array
    {
        return match ($template) {
            'nursery' => [
                ['name' => 'Pre-Nursery', 'code' => 'PREN', 'level' => 1, 'department' => 'Nursery'],
                ['name' => 'Nursery 1', 'code' => 'NUR1', 'level' => 2, 'department' => 'Nursery'],
                ['name' => 'Nursery 2', 'code' => 'NUR2', 'level' => 3, 'department' => 'Nursery'],
            ],
            'kindergarten' => [
                ['name' => 'Kindergarten 1', 'code' => 'KG1', 'level' => 1, 'department' => 'Kindergarten'],
                ['name' => 'Kindergarten 2', 'code' => 'KG2', 'level' => 2, 'department' => 'Kindergarten'],
                ['name' => 'Kindergarten 3', 'code' => 'KG3', 'level' => 3, 'department' => 'Kindergarten'],
            ],
            'primary' => [
                ['name' => 'Primary 1', 'code' => 'PRY1', 'level' => 1, 'department' => 'Primary'],
                ['name' => 'Primary 2', 'code' => 'PRY2', 'level' => 2, 'department' => 'Primary'],
                ['name' => 'Primary 3', 'code' => 'PRY3', 'level' => 3, 'department' => 'Primary'],
                ['name' => 'Primary 4', 'code' => 'PRY4', 'level' => 4, 'department' => 'Primary'],
                ['name' => 'Primary 5', 'code' => 'PRY5', 'level' => 5, 'department' => 'Primary'],
                ['name' => 'Primary 6', 'code' => 'PRY6', 'level' => 6, 'department' => 'Primary'],
            ],
            'secondary' => [
                ['name' => 'JSS 1', 'code' => 'JSS1', 'level' => 1, 'department' => 'Junior Secondary'],
                ['name' => 'JSS 2', 'code' => 'JSS2', 'level' => 2, 'department' => 'Junior Secondary'],
                ['name' => 'JSS 3', 'code' => 'JSS3', 'level' => 3, 'department' => 'Junior Secondary'],
                ['name' => 'SS 1', 'code' => 'SS1', 'level' => 4, 'department' => 'Senior Secondary'],
                ['name' => 'SS 2', 'code' => 'SS2', 'level' => 5, 'department' => 'Senior Secondary'],
                ['name' => 'SS 3', 'code' => 'SS3', 'level' => 6, 'department' => 'Senior Secondary'],
            ],
            'basic' => [
                ['name' => 'Basic 1', 'code' => 'BSC1', 'level' => 1, 'department' => 'Basic'],
                ['name' => 'Basic 2', 'code' => 'BSC2', 'level' => 2, 'department' => 'Basic'],
                ['name' => 'Basic 3', 'code' => 'BSC3', 'level' => 3, 'department' => 'Basic'],
                ['name' => 'Basic 4', 'code' => 'BSC4', 'level' => 4, 'department' => 'Basic'],
                ['name' => 'Basic 5', 'code' => 'BSC5', 'level' => 5, 'department' => 'Basic'],
                ['name' => 'Basic 6', 'code' => 'BSC6', 'level' => 6, 'department' => 'Basic'],
                ['name' => 'Basic 7', 'code' => 'BSC7', 'level' => 7, 'department' => 'Basic'],
                ['name' => 'Basic 8', 'code' => 'BSC8', 'level' => 8, 'department' => 'Basic'],
                ['name' => 'Basic 9', 'code' => 'BSC9', 'level' => 9, 'department' => 'Basic'],
            ],
            'grade' => [
                ['name' => 'Grade 1', 'code' => 'GR1', 'level' => 1, 'department' => 'Grade School'],
                ['name' => 'Grade 2', 'code' => 'GR2', 'level' => 2, 'department' => 'Grade School'],
                ['name' => 'Grade 3', 'code' => 'GR3', 'level' => 3, 'department' => 'Grade School'],
                ['name' => 'Grade 4', 'code' => 'GR4', 'level' => 4, 'department' => 'Grade School'],
                ['name' => 'Grade 5', 'code' => 'GR5', 'level' => 5, 'department' => 'Grade School'],
                ['name' => 'Grade 6', 'code' => 'GR6', 'level' => 6, 'department' => 'Grade School'],
                ['name' => 'Grade 7', 'code' => 'GR7', 'level' => 7, 'department' => 'Middle School'],
                ['name' => 'Grade 8', 'code' => 'GR8', 'level' => 8, 'department' => 'Middle School'],
                ['name' => 'Grade 9', 'code' => 'GR9', 'level' => 9, 'department' => 'Middle School'],
                ['name' => 'Grade 10', 'code' => 'GR10', 'level' => 10, 'department' => 'High School'],
                ['name' => 'Grade 11', 'code' => 'GR11', 'level' => 11, 'department' => 'High School'],
                ['name' => 'Grade 12', 'code' => 'GR12', 'level' => 12, 'department' => 'High School'],
            ],
            'cambridge' => [
                ['name' => 'Year 1', 'code' => 'YR1', 'level' => 1, 'department' => 'Lower Primary'],
                ['name' => 'Year 2', 'code' => 'YR2', 'level' => 2, 'department' => 'Lower Primary'],
                ['name' => 'Year 3', 'code' => 'YR3', 'level' => 3, 'department' => 'Lower Primary'],
                ['name' => 'Year 4', 'code' => 'YR4', 'level' => 4, 'department' => 'Upper Primary'],
                ['name' => 'Year 5', 'code' => 'YR5', 'level' => 5, 'department' => 'Upper Primary'],
                ['name' => 'Year 6', 'code' => 'YR6', 'level' => 6, 'department' => 'Upper Primary'],
                ['name' => 'Year 7', 'code' => 'YR7', 'level' => 7, 'department' => 'Lower Secondary'],
                ['name' => 'Year 8', 'code' => 'YR8', 'level' => 8, 'department' => 'Lower Secondary'],
                ['name' => 'Year 9', 'code' => 'YR9', 'level' => 9, 'department' => 'Lower Secondary'],
                ['name' => 'Year 10', 'code' => 'YR10', 'level' => 10, 'department' => 'IGCSE'],
                ['name' => 'Year 11', 'code' => 'YR11', 'level' => 11, 'department' => 'IGCSE'],
                ['name' => 'Year 12', 'code' => 'YR12', 'level' => 12, 'department' => 'AS Level'],
                ['name' => 'Year 13', 'code' => 'YR13', 'level' => 13, 'department' => 'A Level'],
            ],
            default => [],
        };
    }
}
