<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['school_id', 'ledger_account_id', 'bank_account_id', 'transactionable_type', 'transactionable_id', 'transaction_number', 'transaction_date', 'direction', 'amount', 'description', 'reference', 'status', 'created_by_id', 'notes'])]
class AccountTransaction extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (AccountTransaction $transaction): void {
            if (blank($transaction->transaction_number)) {
                $transaction->transaction_number = 'TXN-'.now()->format('Ymd').'-'.str_pad((string) (static::query()->count() + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
