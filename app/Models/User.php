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

#[Fillable(['name', 'email', 'email_verified_at', 'password', 'is_platform_admin', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if ($user->is_platform_admin) {
                return;
            }

            if (self::query()->doesntExist()) {
                $user->is_platform_admin = true;
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
            'admin' => $this->is_platform_admin || $this->schools()->exists(),
            'school' => $this->is_platform_admin
                ? $this->schoolPanelSchoolsQuery()->exists()
                : $this->schools()->exists(),
            default => false,
        };
    }

    public function getTenants(Panel $panel): array|Collection
    {
        if ($panel->getId() !== 'school') {
            return $this->is_platform_admin
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
        if ($panel->getId() === 'school' && ! $this->is_platform_admin && $this->isParent()) {
            return $this->parentStudentSchoolsQuery()->first();
        }

        return $this->schools()
            ->withoutGlobalScopes()
            ->when($panel->getId() === 'school', fn ($query) => $query->whereNotNull('division'))
            ->orderByDesc('school_user.is_primary')
            ->orderBy('school_user.school_id')
            ->first();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->is_platform_admin) {
            return $this->schoolPanelSchoolsQuery()
                ->whereKey($tenant)
                ->exists();
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

        return $school?->pivot?->role;
    }

    /**
     * @param  array<int, string>|string  $roles
     */
    public function hasSchoolRole(Model|int|null $school, array|string $roles): bool
    {
        return in_array($this->roleForSchool($school), (array) $roles, true);
    }

    protected function isParent(): bool
    {
        return $this->schools()
            ->withoutGlobalScopes()
            ->wherePivot('role', 'parent')
            ->exists();
    }

    protected function parentStudentSchoolsQuery(): Builder
    {
        return School::query()
            ->withoutGlobalScopes()
            ->whereNotNull('division')
            ->whereHas('guardians', fn (Builder $query) => $query
                ->where('user_id', $this->getKey())
                ->whereHas('studentLinks.student'))
            ->orderBy('name');
    }

    protected function schoolPanelSchoolsQuery(): BelongsToMany
    {
        return $this->schools()
            ->withoutGlobalScopes()
            ->whereNotNull('division');
    }
}
