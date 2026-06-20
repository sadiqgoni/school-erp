<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'email_verified_at', 'password', 'role', 'is_platform_admin', 'is_active'])]
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
            if ($user->is_platform_admin || $user->role === self::ROLE_SUPERADMIN) {
                $user->role = self::ROLE_SUPERADMIN;
                $user->is_platform_admin = true;

                return;
            }

            $user->role ??= self::ROLE_USER;
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
                ? School::query()->withoutGlobalScopes()->exists()
                : $this->schools()->exists(),
            default => false,
        };
    }

    public function getTenants(Panel $panel): array|Collection
    {
        if ($panel->getId() !== 'school') {
            return $this->isSuperAdmin()
                ? School::query()->withoutGlobalScopes()->get()
                : $this->schools()->withoutGlobalScopes()->get();
        }

        if ($this->isParent()) {
            return $this->parentStudentSchoolsQuery()->get();
        }

        return $this->schoolPanelSchoolsQuery()->get();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        if ($panel->getId() === 'school' && ! $this->isSuperAdmin() && $this->isParent()) {
            return $this->parentStudentSchoolsQuery()->first();
        }

        if ($panel->getId() === 'school' && $this->isSuperAdmin()) {
            return School::query()
                ->withoutGlobalScopes()
                ->orderBy('name')
                ->first();
        }

        return $this->schools()
            ->withoutGlobalScopes()
            ->orderByDesc('school_user.is_primary')
            ->orderBy('school_user.school_id')
            ->first();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->isSuperAdmin()) {
            return $tenant instanceof School;
        }

        if ($this->isParent()) {
            return Guardian::query()
                ->where('user_id', $this->getKey())
                ->where('school_id', $tenant->getKey())
                ->whereHas('studentLinks.student')
                ->exists();
        }

        return $this->schools()
            ->withoutGlobalScopes()
            ->whereKey($tenant)
            ->exists();
    }

    public function roleForSchool(Model|int|null $school): ?string
    {
        $schoolId = $school instanceof Model ? $school->getKey() : $school;

        if (! $schoolId) {
            return null;
        }

        $school = $this->schools()
            ->withoutGlobalScopes()
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

    public static function normalizeSchoolRole(?string $role): ?string
    {
        return match ($role) {
            'school_admin' => self::SCHOOL_ROLE_ADMIN,
            'platform_admin' => self::ROLE_SUPERADMIN,
            default => $role,
        };
    }

    protected function isParent(): bool
    {
        return $this->schools()
            ->withoutGlobalScopes()
            ->wherePivot('role', self::SCHOOL_ROLE_PARENT)
            ->exists();
    }

    protected function parentStudentSchoolsQuery(): Builder
    {
        return School::query()
            ->withoutGlobalScopes()
            ->whereHas('guardians', fn (Builder $query) => $query
                ->where('user_id', $this->getKey())
                ->whereHas('studentLinks.student'))
            ->orderBy('name');
    }

    protected function schoolPanelSchoolsQuery(): BelongsToMany
    {
        return $this->schools()
            ->withoutGlobalScopes();
    }
}
