<?php

namespace App\Models;

use App\Models\Concerns\CalculatesSalaryItemAmount;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'staff_id',
    'ledger_account_id',
    'type',
    'code',
    'name',
    'calculation_type',
    'value',
    'is_active',
    'notes',
])]
class StaffSalaryAdjustment extends Model
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
            'is_active' => 'boolean',
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

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class);
    }
}
