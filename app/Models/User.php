<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\PasswordChangedNotification;
use App\Notifications\PasswordResetNotification;
use App\Support\CurrentDivision;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

#[Fillable(['name', 'email', 'email_verified_at', 'password', 'role', 'is_platform_admin', 'is_active', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_USER = 'user';

    public const ROLE_SUPERADMIN = 'superadmin';

    public const SCHOOL_ROLE_ADMIN = 'admin';

    public const SCHOOL_ROLE_TEACHER = 'teacher';

    public const SCHOOL_ROLE_STAFF = 'staff';

    public const SCHOOL_ROLE_FINANCE = 'finance';

    public const SCHOOL_ROLE_PARENT = 'parent';

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if ($user->isSuperAdmin()) {
                $user->role = self::ROLE_SUPERADMIN;
                $user->is_platform_admin = true;

                return;
            }

            if (self::query()->doesntExist()) {
                $user->role = self::ROLE_SUPERADMIN;
                $user->is_platform_admin = true;
            }
        });

        static::saving(function (self $user): void {
            // A real password change clears the forced-change flag.
            if ($user->exists && $user->isDirty('password') && ! $user->isDirty('must_change_password')) {
                $user->must_change_password = false;
            }

            if ($user->is_platform_admin || $user->role === self::ROLE_SUPERADMIN) {
                $user->role = self::ROLE_SUPERADMIN;
                $user->is_platform_admin = true;

                return;
            }

            $user->role ??= self::ROLE_USER;
        });

        static::updated(function (self $user): void {
            if (! $user->wasChanged('password')) {
                return;
            }

            try {
                Notification::sendNow($user, new PasswordChangedNotification);
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class)
            ->withPivot(['role', 'is_primary'])
            ->withTimestamps();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->isSuperAdmin() || $this->schools()->exists(),
            'school' => $this->isSuperAdmin()
                ? School::query()->withoutGlobalScope('school-panel-current-tenant')->exists()
                : $this->schools()->exists(),
            default => false,
        };
    }

    public function getTenants(Panel $panel): array|Collection
    {
        if ($panel->getId() !== 'school') {
            return $this->isSuperAdmin()
                ? School::query()->withoutGlobalScope('school-panel-current-tenant')->get()
                : $this->schools()->withoutGlobalScope('school-panel-current-tenant')->get();
        }

        // The Filament tenant is the parent/client school; a user's actual
        // access is per-division (school_user pivot / Guardian links), so
        // every accessible division gets mapped up to its parent here and
        // deduplicated — a user with access to two divisions of the same
        // client school sees that client once in the tenant switcher, with
        // the division picker (App\Filament\Pages\SelectDivision) handling
        // the rest once they're inside it.
        return CurrentDivision::allAccessibleWorkspaces($this)
            ->map(fn (School $division): ?School => $division->parent_school_id
                ? School::query()->withoutGlobalScope('school-panel-current-tenant')->find($division->parent_school_id)
                : $division)
            ->filter()
            ->unique('id')
            ->sortBy([['is_active', 'desc'], ['name', 'asc']])
            ->values();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        if ($panel->getId() === 'school') {
            $primaryDivision = $this->schools()
                ->withoutGlobalScope('school-panel-current-tenant')
                ->orderByDesc('school_user.is_primary')
                ->orderBy('school_user.school_id')
                ->first();

            if ($primaryDivision) {
                return $primaryDivision->parent_school_id
                    ? School::query()->withoutGlobalScope('school-panel-current-tenant')->find($primaryDivision->parent_school_id)
                    : $primaryDivision;
            }

            // Guardian-only users have no school_user pivot row at all —
            // fall back to the first client school they can reach any way.
            return collect($this->getTenants($panel))->first();
        }

        return $this->schools()
            ->withoutGlobalScope('school-panel-current-tenant')
            ->orderByDesc('school_user.is_primary')
            ->orderBy('school_user.school_id')
            ->first();
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $route = $this->isSuperAdmin()
            ? 'filament.admin.auth.password-reset.reset'
            : 'filament.school.auth.password-reset.reset';

        $url = URL::signedRoute($route, [
            'email' => $this->getEmailForPasswordReset(),
            'token' => $token,
        ]);

        // Filament's base ResetPassword notification implements ShouldQueue.
        // QUEUE_CONNECTION=database has no worker running in this deployment,
        // so a queued send would sit in the jobs table forever and never
        // arrive. sendNow() bypasses the queue for this one critical,
        // time-sensitive path so the reset link actually reaches the inbox.
        Notification::sendNow($this, new PasswordResetNotification($token, $url));
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if (! $tenant instanceof School) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        // Access to the tenant (the parent/client school) means having
        // access to at least one division underneath it — works identically
        // whether $tenant is itself a division (today) or a parent with
        // children (post subdomain-consolidation), since availableFor()
        // resolves "this row or its children" either way. Deliberately not
        // using CurrentDivision::get() here — this check runs before any
        // division could possibly be selected yet.
        return CurrentDivision::availableFor($this, $tenant)->isNotEmpty();
    }

    public function roleForSchool(Model|int|null $school): ?string
    {
        $schoolId = $school instanceof Model ? $school->getKey() : $school;

        if (! $schoolId) {
            return null;
        }

        $school = $this->schools()
            ->withoutGlobalScope('school-panel-current-tenant')
            ->whereKey($schoolId)
            ->first();

        return self::normalizeSchoolRole($school?->pivot?->role);
    }

    /**
     * @param  array<int, string>|string  $roles
     */
    public function hasSchoolRole(Model|int|null $school, array|string $roles): bool
    {
        $roles = array_map(
            fn (string $role): string => self::normalizeSchoolRole($role),
            (array) $roles,
        );

        return in_array($this->roleForSchool($school), $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN || (bool) $this->is_platform_admin;
    }

    public function canAdministerSchool(Model|int|null $school): bool
    {
        return $this->isSuperAdmin()
            || $this->hasSchoolRole($school, self::SCHOOL_ROLE_ADMIN);
    }

    public function canManageSchoolFinances(Model|int|null $school): bool
    {
        return $this->isSuperAdmin()
            || $this->hasSchoolRole($school, [self::SCHOOL_ROLE_ADMIN, self::SCHOOL_ROLE_FINANCE]);
    }

    public static function normalizeSchoolRole(?string $role): ?string
    {
        return match ($role) {
            'school_admin' => self::SCHOOL_ROLE_ADMIN,
            'platform_admin' => self::ROLE_SUPERADMIN,
            default => $role,
        };
    }
}
