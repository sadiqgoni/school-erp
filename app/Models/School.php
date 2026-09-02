<?php

namespace App\Models;

use App\Support\CurrentDivision;
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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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
    'withhold_results_for_debtors',
])]
class School extends Model implements HasAvatar, HasName
{
    use HasFactory, SoftDeletes;

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

            // No tenant resolved yet means this query IS Filament's own
            // tenant-resolution lookup (or something running before it) —
            // must stay unscoped, or that lookup can never find anything.
            if (($panel?->getId() !== 'school') || (! $tenant)) {
                return;
            }

            // The Filament tenant is the parent/client school; the row a
            // school-panel request is actually scoped to is whichever
            // division the user has selected for this session. No division
            // resolved yet means no rows — never fall back to "all rows".
            $query->whereKey(CurrentDivision::get()?->getKey());
        });

        static::deleting(function (School $school): void {
            if ($school->isForceDeleting()) {
                $school->divisions()
                    ->withoutGlobalScope('school-panel-current-tenant')
                    ->withTrashed()
                    ->get()
                    ->each->forceDelete();

                return;
            }

            $school->divisions()
                ->withoutGlobalScope('school-panel-current-tenant')
                ->get()
                ->each->delete();

            $school->softDeleteRecoverableSchoolRecords();
        });

        static::restored(function (School $school): void {
            $school->divisions()
                ->withoutGlobalScope('school-panel-current-tenant')
                ->onlyTrashed()
                ->get()
                ->each->restore();

            $school->restoreRecoverableSchoolRecords();
        });
    }

    protected function casts(): array
    {
        return [
            'enabled_modules' => 'array',
            'is_active' => 'boolean',
            'subscription_expires_at' => 'datetime',
            'withhold_results_for_debtors' => 'boolean',
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
        return $this->logoUrl();
    }

    public function displayLogoPath(): ?string
    {
        if (filled($this->logo_path)) {
            return $this->logo_path;
        }

        if ($this->parent_school_id) {
            return self::query()
                ->withoutGlobalScope('school-panel-current-tenant')
                ->whereKey($this->parent_school_id)
                ->value('logo_path');
        }

        return self::query()
            ->withoutGlobalScope('school-panel-current-tenant')
            ->where('parent_school_id', $this->getKey())
            ->whereNotNull('logo_path')
            ->where('logo_path', '!=', '')
            ->orderBy('id')
            ->value('logo_path');
    }

    public function logoUrl(): ?string
    {
        $logoPath = $this->displayLogoPath();

        if (blank($logoPath)) {
            return null;
        }

        if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')) {
            return $logoPath;
        }

        return Storage::disk('public')->url($logoPath);
    }

    public function baseSchoolName(): string
    {
        if (! $this->parent_school_id) {
            return $this->name;
        }

        return self::query()
            ->withoutGlobalScope('school-panel-current-tenant')
            ->whereKey($this->parent_school_id)
            ->value('name') ?? $this->name;
    }

    public function divisionLabel(): ?string
    {
        return self::DIVISIONS[$this->division] ?? null;
    }

    public function portalSchool(): self
    {
        if ($this->division) {
            return $this;
        }

        return self::query()
            ->withoutGlobalScope('school-panel-current-tenant')
            ->where('parent_school_id', $this->getKey())
            ->where('is_active', true)
            ->orderByRaw("case division when 'nursery' then 1 when 'primary' then 2 when 'secondary' then 3 else 4 end")
            ->orderBy('id')
            ->first() ?? $this;
    }

    /**
     * A root school that owns division records is an administrative container,
     * not a portal workspace. Legacy schools without divisions remain valid
     * workspaces so existing installations are not locked out.
     */
    public function isPortalWorkspace(): bool
    {
        return filled($this->division)
            || ! self::query()
                ->withoutGlobalScope('school-panel-current-tenant')
                ->where('parent_school_id', $this->getKey())
                ->exists();
    }

    public function isSubscriptionExpired(): bool
    {
        return $this->subscription_expires_at?->isPast() ?? false;
    }

    public function isAvailableToSchoolUsers(): bool
    {
        return (bool) $this->is_active && ! $this->isSubscriptionExpired();
    }

    public function scopePortalWorkspaces(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->whereNotNull('division')
                ->orWhereDoesntHave('divisions');
        });
    }

    /**
     * The subdomain now covers a whole client school, all its divisions
     * included — so a link generated with a specific division in mind (a
     * parent-notification email, a staff/guardian credential email) needs a
     * way to land the visitor directly in that division's context rather
     * than the division picker. $withDivisionHint routes the link through a
     * signed landing URL (PortalDivisionLinkController) that pre-seeds the
     * division into session before handing off to the portal proper; pass
     * false only when the caller genuinely wants the bare subdomain root
     * (e.g. displaying the URL itself in an admin table).
     */
    public function portalUrl(string $path = '', bool $withDivisionHint = true): string
    {
        $division = $this->portalSchool();
        $parent = $division->parent_school_id
            ? self::query()->withoutGlobalScope('school-panel-current-tenant')->find($division->parent_school_id)
            : $division;

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $domain = config('app.central_domain');
        $baseUrl = "{$scheme}://{$parent->slug}.{$domain}/portal".$path;

        if (! $withDivisionHint || $parent->getKey() === $division->getKey()) {
            return $baseUrl;
        }

        return URL::signedRoute('portal.division-hint', [
            'school' => $parent->getKey(),
            'division' => $division->getKey(),
            'redirect' => $path,
        ]);
    }

    protected function softDeleteRecoverableSchoolRecords(): void
    {
        foreach ($this->recoverableSchoolRecordModels() as $model) {
            $model::query()
                ->withoutGlobalScopes()
                ->where('school_id', $this->getKey())
                ->get()
                ->each->delete();
        }
    }

    protected function restoreRecoverableSchoolRecords(): void
    {
        foreach ($this->recoverableSchoolRecordModels() as $model) {
            $model::query()
                ->withoutGlobalScopes()
                ->onlyTrashed()
                ->where('school_id', $this->getKey())
                ->get()
                ->each->restore();
        }
    }

    /**
     * Models with SoftDeletes that should disappear with the school but remain
     * restorable when a school delete was a mistake.
     *
     * @return array<class-string<Model>>
     */
    protected function recoverableSchoolRecordModels(): array
    {
        return [
            FeePayment::class,
            StudentInvoice::class,
            AccountTransaction::class,
            SalaryPosting::class,
            Student::class,
        ];
    }

    protected function slug(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value, array $attributes) => Str::slug($value ?: ($attributes['name'] ?? '')),
        );
    }
}
