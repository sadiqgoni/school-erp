<?php

namespace App\Support;

use App\Models\School;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SchoolDivisionProvisioner
{
    public static function provision(School $school, ?array $divisions = null): Collection
    {
        if ($school->division) {
            return collect([$school]);
        }

        return collect($divisions ?: array_keys(School::DIVISIONS))
            ->filter(fn (string $division): bool => array_key_exists($division, School::DIVISIONS))
            ->values()
            ->map(fn (string $division): School => self::provisionDivision($school, $division));
    }

    public static function attachUsersFromParent(School $school, ?array $divisions = null): void
    {
        if ($school->division) {
            return;
        }

        $divisionSchools = self::provision($school, $divisions);

        $school->users()
            ->withPivot(['role', 'is_primary'])
            ->get()
            ->each(function ($user) use ($divisionSchools): void {
                $divisionSchools->each(function (School $divisionSchool, int $index) use ($user): void {
                    $user->schools()->syncWithoutDetaching([
                        $divisionSchool->getKey() => [
                            'role' => $user->pivot->role ?? 'school_admin',
                            'is_primary' => (bool) ($user->pivot->is_primary ?? false) && $index === 0,
                        ],
                    ]);
                });
            });
    }

    protected static function provisionDivision(School $school, string $division): School
    {
        return School::query()->firstOrCreate(
            [
                'parent_school_id' => $school->getKey(),
                'division' => $division,
            ],
            [
                ...Arr::only($school->getAttributes(), [
                    'email',
                    'phone',
                    'address',
                    'city',
                    'state',
                    'country',
                    'logo_path',
                    'primary_color',
                    'subscription_plan',
                    'subscription_expires_at',
                    'student_limit',
                    'enabled_modules',
                    'is_active',
                ]),
                'name' => $school->name,
                'code' => self::divisionCode($school->code, $division),
                'slug' => "{$school->slug}-{$division}",
            ],
        );
    }

    protected static function divisionCode(string $code, string $division): string
    {
        $suffix = match ($division) {
            School::DIVISION_NURSERY => 'NUR',
            School::DIVISION_PRIMARY => 'PRI',
            School::DIVISION_SECONDARY => 'SEC',
            default => strtoupper(Str::limit($division, 3, '')),
        };

        return Str::limit("{$code}-{$suffix}", 30, '');
    }
}
