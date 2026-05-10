<?php

namespace App\Support;

use App\Models\School;
use App\Models\User;
use App\Models\UserActivity;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public static function log(
        string $action,
        ?string $description = null,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null,
        ?Request $request = null,
        ?int $schoolId = null,
    ): void {
        $request ??= request();
        $authUser = Auth::user();
        $user ??= $authUser instanceof User ? $authUser : null;

        UserActivity::query()->create([
            'user_id' => $user?->getKey(),
            'school_id' => $schoolId ?? self::resolveSchoolId($auditable),
            'action' => $action,
            'description' => $description,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => self::sanitize($oldValues),
            'new_values' => self::sanitize($newValues),
            'panel' => Filament::getCurrentPanel()?->getId(),
            'url' => $request?->fullUrl(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach (['password', 'remember_token'] as $hiddenField) {
            if (array_key_exists($hiddenField, $values)) {
                $values[$hiddenField] = '[hidden]';
            }
        }

        return $values;
    }

    protected static function resolveSchoolId(?Model $auditable): ?int
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof School) {
            return $tenant->getKey();
        }

        if ($auditable && isset($auditable->school_id)) {
            return (int) $auditable->school_id;
        }

        return null;
    }
}
