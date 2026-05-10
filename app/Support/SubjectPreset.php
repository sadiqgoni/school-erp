<?php

namespace App\Support;

class SubjectPreset
{
    public static function options(): array
    {
        return [
            'nursery' => 'Nursery',
            'primary' => 'Primary',
            'junior_secondary' => 'Junior Secondary',
            'senior_secondary' => 'Senior Secondary',
            'common' => 'Common/Core',
        ];
    }

    public static function defaults(array $templates): array
    {
        $subjects = collect($templates)
            ->flatMap(fn (string $template): array => static::template($template))
            ->unique('code')
            ->values()
            ->all();

        return array_map(fn (array $subject): array => $subject + ['is_active' => true], $subjects);
    }

    protected static function template(string $template): array
    {
        return match ($template) {
            'nursery' => [
                ['name' => 'Rhymes', 'code' => 'RHY', 'department' => 'Nursery'],
                ['name' => 'Number Work', 'code' => 'NUM', 'department' => 'Nursery'],
                ['name' => 'Letter Work', 'code' => 'LET', 'department' => 'Nursery'],
                ['name' => 'Phonics', 'code' => 'PHN', 'department' => 'Nursery'],
                ['name' => 'Writing', 'code' => 'WRT', 'department' => 'Nursery'],
            ],
            'primary' => [
                ['name' => 'English Language', 'code' => 'ENG', 'department' => 'Primary'],
                ['name' => 'Mathematics', 'code' => 'MTH', 'department' => 'Primary'],
                ['name' => 'Basic Science', 'code' => 'BSC', 'department' => 'Primary'],
                ['name' => 'Social Studies', 'code' => 'SOS', 'department' => 'Primary'],
                ['name' => 'Civic Education', 'code' => 'CVE', 'department' => 'Primary'],
                ['name' => 'Verbal Reasoning', 'code' => 'VBR', 'department' => 'Primary'],
                ['name' => 'Quantitative Reasoning', 'code' => 'QTR', 'department' => 'Primary'],
                ['name' => 'Computer Studies', 'code' => 'CMP', 'department' => 'Primary'],
            ],
            'junior_secondary' => [
                ['name' => 'English Language', 'code' => 'ENG', 'department' => 'Junior Secondary'],
                ['name' => 'Mathematics', 'code' => 'MTH', 'department' => 'Junior Secondary'],
                ['name' => 'Basic Science', 'code' => 'BSC', 'department' => 'Junior Secondary'],
                ['name' => 'Basic Technology', 'code' => 'BTE', 'department' => 'Junior Secondary'],
                ['name' => 'Civic Education', 'code' => 'CVE', 'department' => 'Junior Secondary'],
                ['name' => 'Business Studies', 'code' => 'BST', 'department' => 'Junior Secondary'],
                ['name' => 'Agricultural Science', 'code' => 'AGR', 'department' => 'Junior Secondary'],
                ['name' => 'Computer Studies', 'code' => 'CMP', 'department' => 'Junior Secondary'],
            ],
            'senior_secondary' => [
                ['name' => 'English Language', 'code' => 'ENG', 'department' => 'Senior Secondary'],
                ['name' => 'Mathematics', 'code' => 'MTH', 'department' => 'Senior Secondary'],
                ['name' => 'Biology', 'code' => 'BIO', 'department' => 'Senior Secondary'],
                ['name' => 'Chemistry', 'code' => 'CHM', 'department' => 'Senior Secondary'],
                ['name' => 'Physics', 'code' => 'PHY', 'department' => 'Senior Secondary'],
                ['name' => 'Economics', 'code' => 'ECO', 'department' => 'Senior Secondary'],
                ['name' => 'Government', 'code' => 'GOV', 'department' => 'Senior Secondary'],
                ['name' => 'Literature in English', 'code' => 'LIT', 'department' => 'Senior Secondary'],
            ],
            'common' => [
                ['name' => 'Physical and Health Education', 'code' => 'PHE', 'department' => 'General'],
                ['name' => 'Creative Arts', 'code' => 'CRA', 'department' => 'General'],
                ['name' => 'CRS/IRS', 'code' => 'REL', 'department' => 'General'],
            ],
            default => [],
        };
    }
}
