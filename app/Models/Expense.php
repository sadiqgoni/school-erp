<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_id', 'expense_category_id', 'expense_number', 'expense_date', 'payee', 'description', 'amount', 'payment_method', 'bank_account_id', 'asset_account_id', 'expense_account_id', 'reference', 'recorded_by_id', 'status', 'notes'])]
class Expense extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Expense $expense): void {
            if (blank($expense->expense_number)) {
                $expense->expense_number = 'EXP-'.now()->format('Ymd').'-'.str_pad((string) (static::query()->count() + 1), 4, '0', STR_PAD_LEFT);
            }
        });

        static::saved(function (Expense $expense): void {
            $expense->syncTransactions();
        });
    }

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'asset_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'expense_account_id');
    }

    public function syncTransactions(): void
    {
        if (! in_array($this->status, ['approved', 'paid'], true) || ! $this->expense_account_id) {
            return;
        }

        AccountTransaction::query()->updateOrCreate(
            [
                'transactionable_type' => self::class,
                'transactionable_id' => $this->getKey(),
                'ledger_account_id' => $this->expense_account_id,
                'direction' => 'debit',
            ],
            [
                'school_id' => $this->school_id,
                'bank_account_id' => $this->bank_account_id,
                'transaction_date' => $this->expense_date,
                'amount' => $this->amount,
                'description' => $this->description,
                'reference' => $this->reference,
                'status' => 'posted',
                'created_by_id' => $this->recorded_by_id,
                'notes' => $this->notes,
            ],
        );

        if (! $this->asset_account_id) {
            return;
        }

        AccountTransaction::query()->updateOrCreate(
            [
                'transactionable_type' => self::class,
                'transactionable_id' => $this->getKey(),
                'ledger_account_id' => $this->asset_account_id,
                'direction' => 'credit',
            ],
            [
                'school_id' => $this->school_id,
                'bank_account_id' => $this->bank_account_id,
                'transaction_date' => $this->expense_date,
                'amount' => $this->amount,
                'description' => 'Expense payment '.$this->expense_number,
                'reference' => $this->reference,
                'status' => 'posted',
                'created_by_id' => $this->recorded_by_id,
                'notes' => $this->notes,
            ],
        );
    }
}
