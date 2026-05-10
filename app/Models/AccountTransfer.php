<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_id', 'from_account_id', 'to_account_id', 'transfer_number', 'transfer_date', 'amount', 'reference', 'status', 'created_by_id', 'notes'])]
class AccountTransfer extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (AccountTransfer $transfer): void {
            if (blank($transfer->transfer_number)) {
                $transfer->transfer_number = 'TRF-'.now()->format('Ymd').'-'.str_pad((string) (static::query()->count() + 1), 5, '0', STR_PAD_LEFT);
            }
        });

        static::saved(function (AccountTransfer $transfer): void {
            if ($transfer->status !== 'posted') {
                return;
            }

            AccountTransaction::query()->updateOrCreate(
                [
                    'transactionable_type' => self::class,
                    'transactionable_id' => $transfer->getKey(),
                    'ledger_account_id' => $transfer->from_account_id,
                    'direction' => 'credit',
                ],
                [
                    'school_id' => $transfer->school_id,
                    'transaction_date' => $transfer->transfer_date,
                    'amount' => $transfer->amount,
                    'description' => 'Transfer out '.$transfer->transfer_number,
                    'reference' => $transfer->reference,
                    'status' => 'posted',
                    'created_by_id' => $transfer->created_by_id,
                    'notes' => $transfer->notes,
                ],
            );

            AccountTransaction::query()->updateOrCreate(
                [
                    'transactionable_type' => self::class,
                    'transactionable_id' => $transfer->getKey(),
                    'ledger_account_id' => $transfer->to_account_id,
                    'direction' => 'debit',
                ],
                [
                    'school_id' => $transfer->school_id,
                    'transaction_date' => $transfer->transfer_date,
                    'amount' => $transfer->amount,
                    'description' => 'Transfer in '.$transfer->transfer_number,
                    'reference' => $transfer->reference,
                    'status' => 'posted',
                    'created_by_id' => $transfer->created_by_id,
                    'notes' => $transfer->notes,
                ],
            );
        });
    }

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'to_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
