<?php

namespace App\Models;

use App\Models\Concerns\CalculatesSalaryItemAmount;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'salary_template_id',
    'ledger_account_id',
    'type',
    'code',
    'name',
    'grade_level',
    'step',
    'calculation_type',
    'calculation_details',
    'value',
    'is_active',
    'notes',
])]
class PayrollItemType extends Model
{
    use CalculatesSalaryItemAmount;
    use Concerns\BelongsToSchool;
    use HasFactory;

    public const TYPE_ALLOWANCE = 'allowance';

    public const TYPE_DEDUCTION = 'deduction';

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'calculation_details' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function salaryTemplate(): BelongsTo
    {
        return $this->belongsTo(SalaryTemplate::class);
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class);
    }
}
