<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'school_id',
    'user_id',
    'department_id',
    'staff_type',
    'staff_number',
    'first_name',
    'middle_name',
    'last_name',
    'gender',
    'date_of_birth',
    'phone',
    'email',
    'address',
    'city',
    'state',
    'country',
    'employment_type',
    'job_title',
    'highest_qualification',
    'course_specialization',
    'education_school',
    'trcn_number',
    'hire_date',
    'basic_salary',
    'bank_name',
    'bank_account_name',
    'bank_account_number',
    'status',
    'photo_path',
    'next_of_kin_name',
    'next_of_kin_relation',
    'next_of_kin_phone',
    'next_of_kin_occupation',
    'next_of_kin_address',
    'notes',
])]
class Staff extends Model
{
    use HasFactory;

    public const TYPE_TEACHING = 'teaching';

    public const TYPE_NON_TEACHING = 'non_teaching';

    public const QUALIFICATION_OPTIONS = [
        'fslc' => 'FSLC',
        'ssce' => 'SSCE / WAEC / NECO',
        'nce' => 'NCE',
        'ond' => 'OND',
        'hnd' => 'HND',
        'b_ed' => 'B.Ed',
        'b_sc' => 'B.Sc',
        'b_a' => 'B.A',
        'pgde' => 'PGDE',
        'm_ed' => 'M.Ed',
        'm_sc' => 'M.Sc',
        'phd' => 'PhD',
        'other' => 'Other',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'basic_salary' => 'decimal:2',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(StaffRoleAssignment::class);
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ])->filter()->join(' '));
    }

    protected function isTeacher(): Attribute
    {
        return Attribute::get(fn (): bool => $this->staff_type === self::TYPE_TEACHING);
    }
}
