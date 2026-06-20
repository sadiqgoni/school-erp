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
    'code',
    'name',
    'grade_level',
    'step',
    'calculation_type',
    'value',
    'is_active',
    'notes',
])]
class SalaryDeduction extends Model
{
    use CalculatesSalaryItemAmount;
    use HasFactory;

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
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
