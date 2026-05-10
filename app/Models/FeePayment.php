<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_id', 'student_invoice_id', 'student_id', 'receipt_number', 'payer', 'payment_date', 'amount', 'payment_method', 'bank_account_id', 'asset_account_id', 'income_account_id', 'reference', 'received_by_id', 'status', 'notes'])]
class FeePayment extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (FeePayment $payment): void {
            if (blank($payment->receipt_number)) {
                $payment->receipt_number = 'RCP-'.now()->format('Ymd').'-'.str_pad((string) (static::query()->count() + 1), 4, '0', STR_PAD_LEFT);
            }
        });

        static::saved(function (FeePayment $payment): void {
            $payment->studentInvoice?->refreshAmounts();
            $payment->syncTransactions();
        });

        static::deleted(function (FeePayment $payment): void {
            $payment->studentInvoice?->refreshAmounts();
        });
    }

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studentInvoice(): BelongsTo
    {
        return $this->belongsTo(StudentInvoice::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'asset_account_id');
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'income_account_id');
    }

    public function syncTransactions(): void
    {
        if ($this->status !== 'confirmed' || ! $this->asset_account_id) {
            return;
        }

        AccountTransaction::query()->updateOrCreate(
            [
                'transactionable_type' => self::class,
                'transactionable_id' => $this->getKey(),
                'ledger_account_id' => $this->asset_account_id,
                'direction' => 'debit',
            ],
            [
                'school_id' => $this->school_id,
                'bank_account_id' => $this->bank_account_id,
                'transaction_date' => $this->payment_date,
                'amount' => $this->amount,
                'description' => 'Payment received '.$this->receipt_number,
                'reference' => $this->reference,
                'status' => 'posted',
                'created_by_id' => $this->received_by_id,
                'notes' => $this->notes,
            ],
        );

        if (! $this->income_account_id) {
            return;
        }

        AccountTransaction::query()->updateOrCreate(
            [
                'transactionable_type' => self::class,
                'transactionable_id' => $this->getKey(),
                'ledger_account_id' => $this->income_account_id,
                'direction' => 'credit',
            ],
            [
                'school_id' => $this->school_id,
                'bank_account_id' => $this->bank_account_id,
                'transaction_date' => $this->payment_date,
                'amount' => $this->amount,
                'description' => 'Fee income '.$this->receipt_number,
                'reference' => $this->reference,
                'status' => 'posted',
                'created_by_id' => $this->received_by_id,
                'notes' => $this->notes,
            ],
        );
    }
}
