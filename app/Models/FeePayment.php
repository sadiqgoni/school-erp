<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['school_id', 'student_invoice_id', 'student_id', 'receipt_number', 'payer', 'payment_date', 'amount', 'payment_method', 'payment_provider', 'provider_transaction_id', 'provider_payload', 'bank_account_id', 'asset_account_id', 'income_account_id', 'reference', 'received_by_id', 'status', 'acknowledged_at', 'acknowledged_by_id', 'notes'])]
class FeePayment extends Model
{
    use Concerns\BelongsToSchool;
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (FeePayment $payment): void {
            if (blank($payment->receipt_number)) {
                $payment->receipt_number = 'RCP-'.now()->format('Ymd').'-'.str_pad((string) (static::query()->withoutGlobalScopes()->where('school_id', $payment->school_id)->count() + 1), 4, '0', STR_PAD_LEFT);
            }
        });

        static::saving(function (FeePayment $payment): void {
            $payment->assignDefaultAccounts();
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
            'provider_payload' => 'array',
            'acknowledged_at' => 'datetime',
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

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by_id');
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

    public function communicationLogs()
    {
        return $this->morphMany(CommunicationLog::class, 'related');
    }

    public function syncTransactions(): void
    {
        if ($this->status !== 'confirmed' || ! $this->asset_account_id) {
            $this->transactionsQuery()->delete();

            return;
        }

        $this->transactionsQuery()
            ->whereNotIn('ledger_account_id', array_filter([
                $this->asset_account_id,
                $this->income_account_id,
            ]))
            ->delete();

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
            $this->transactionsQuery()
                ->where('direction', 'credit')
                ->delete();

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

    protected function assignDefaultAccounts(): void
    {
        if (! $this->school_id) {
            return;
        }

        if (! $this->bank_account_id) {
            $this->bank_account_id = BankAccount::query()
                ->where('school_id', $this->school_id)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->value('id');
        }

        if (! $this->asset_account_id) {
            $this->asset_account_id = $this->defaultAssetAccount()?->getKey();
        }

        if (! $this->income_account_id) {
            $this->income_account_id = $this->studentInvoice?->income_account_id
                ?: $this->defaultIncomeAccount()?->getKey();
        }
    }

    protected function defaultAssetAccount(): ?LedgerAccount
    {
        $account = LedgerAccount::query()
            ->where('school_id', $this->school_id)
            ->where('type', 'asset')
            ->where('is_active', true)
            ->orderByRaw("case when lower(name) like '%cash%' or lower(name) like '%bank%' then 0 else 1 end")
            ->orderBy('code')
            ->first();

        if ($account) {
            return $account;
        }

        return LedgerAccount::query()->updateOrCreate(
            [
                'school_id' => $this->school_id,
                'code' => '1000',
            ],
            [
                'name' => 'Cash and Bank',
                'type' => 'asset',
                'opening_balance' => 0,
                'is_system' => true,
                'is_active' => true,
                'description' => 'System account for school fee collections when no asset account has been selected.',
            ],
        );
    }

    protected function defaultIncomeAccount(): ?LedgerAccount
    {
        $account = LedgerAccount::query()
            ->where('school_id', $this->school_id)
            ->where('type', 'income')
            ->where('is_active', true)
            ->orderByRaw("case when lower(name) like '%fee%' or lower(name) like '%tuition%' then 0 else 1 end")
            ->orderBy('code')
            ->first();

        if ($account) {
            return $account;
        }

        return LedgerAccount::query()->updateOrCreate(
            [
                'school_id' => $this->school_id,
                'code' => '4000',
            ],
            [
                'name' => 'School Fee Income',
                'type' => 'income',
                'opening_balance' => 0,
                'is_system' => true,
                'is_active' => true,
                'description' => 'System account for tuition and student fee revenue.',
            ],
        );
    }

    protected function transactionsQuery()
    {
        return AccountTransaction::query()
            ->where('transactionable_type', self::class)
            ->where('transactionable_id', $this->getKey());
    }
}
