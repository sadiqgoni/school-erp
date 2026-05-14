<?php

namespace App\Models;

use Filament\Facades\Filament;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'parent_school_id',
    'division',
    'name',
    'code',
    'slug',
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
])]
class School extends Model implements HasAvatar, HasName
{
    use HasFactory;

    public const DIVISION_NURSERY = 'nursery';

    public const DIVISION_PRIMARY = 'primary';

    public const DIVISION_SECONDARY = 'secondary';

    public const DIVISIONS = [
        self::DIVISION_NURSERY => 'Nursery Section',
        self::DIVISION_PRIMARY => 'Primary Section',
        self::DIVISION_SECONDARY => 'Secondary Section',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('school-panel-current-tenant', function (Builder $query): void {
            $panel = Filament::getCurrentPanel();
            $tenant = Filament::getTenant();

            if (($panel?->getId() !== 'school') || (! $tenant)) {
                return;
            }

            $query->whereKey($tenant);
        });
    }

    protected function casts(): array
    {
        return [
            'enabled_modules' => 'array',
            'is_active' => 'boolean',
            'subscription_expires_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'is_primary'])
            ->withTimestamps();
    }

    public function parentSchool(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_school_id');
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_school_id');
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function schoolClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function studentAttendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function staffAttendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class);
    }

    public function feeTypes(): HasMany
    {
        return $this->hasMany(FeeType::class);
    }

    public function studentInvoices(): HasMany
    {
        return $this->hasMany(StudentInvoice::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function getFilamentName(): string
    {
        return $this->divisionLabel() ?? $this->name;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (blank($this->logo_path)) {
            return null;
        }

        if (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://')) {
            return $this->logo_path;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    public function baseSchoolName(): string
    {
        if (! $this->parent_school_id) {
            return $this->name;
        }

        return self::query()
            ->withoutGlobalScopes()
            ->whereKey($this->parent_school_id)
            ->value('name') ?? $this->name;
    }

    public function divisionLabel(): ?string
    {
        return self::DIVISIONS[$this->division] ?? null;
    }

    protected function slug(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value, array $attributes) => $value ?: Str::slug($attributes['name'] ?? ''),
        );
    }
}
