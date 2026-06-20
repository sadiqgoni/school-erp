<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'staff_id',
    'salary_template_id',
    'payroll_month',
    'reference',
    'staff_number',
    'staff_name',
    'grade_level',
    'step',
    'basic_salary',
    'allowances_total',
    'gross_pay',
    'deductions_total',
    'net_pay',
    'allowance_breakdown',
    'deduction_breakdown',
    'status',
    'posted_at',
    'posted_by_id',
    'notes',
])]
class SalaryPosting extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'payroll_month' => 'date',
            'basic_salary' => 'decimal:2',
            'allowances_total' => 'decimal:2',
            'gross_pay' => 'decimal:2',
            'deductions_total' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'allowance_breakdown' => 'array',
            'deduction_breakdown' => 'array',
            'posted_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function salaryTemplate(): BelongsTo
    {
        return $this->belongsTo(SalaryTemplate::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }
}
