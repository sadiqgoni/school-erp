<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'school_id',
    'name',
    'code',
    'grade_level',
    'step',
    'monthly_basic',
    'annual_basic',
    'housing_allowance',
    'transport_allowance',
    'meal_allowance',
    'other_allowance',
    'pension_deduction',
    'tax_deduction',
    'other_deduction',
    'is_active',
    'notes',
])]
class SalaryTemplate extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (SalaryTemplate $template): void {
            if (blank($template->code) && filled($template->name)) {
                $template->code = Str::upper(Str::slug($template->name, '-'));
            }

            if ((float) $template->annual_basic <= 0 && (float) $template->monthly_basic > 0) {
                $template->annual_basic = round((float) $template->monthly_basic * 12, 2);
            }

            if ((float) $template->monthly_basic <= 0 && (float) $template->annual_basic > 0) {
                $template->monthly_basic = round((float) $template->annual_basic / 12, 2);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'monthly_basic' => 'decimal:2',
            'annual_basic' => 'decimal:2',
            'housing_allowance' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'meal_allowance' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'pension_deduction' => 'decimal:2',
            'tax_deduction' => 'decimal:2',
            'other_deduction' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(SalaryAllowance::class);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(SalaryDeduction::class);
    }

    public function salaryPostings(): HasMany
    {
        return $this->hasMany(SalaryPosting::class);
    }
}
