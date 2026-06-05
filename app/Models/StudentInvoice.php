<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_id', 'student_id', 'academic_year_id', 'term_id', 'student_discount_id', 'income_account_id', 'invoice_number', 'invoice_type', 'invoice_date', 'due_date', 'subtotal', 'discount', 'total', 'amount_paid', 'balance', 'status', 'payment_provider', 'payment_reference', 'payment_url', 'payment_status', 'payment_metadata', 'notes'])]
class StudentInvoice extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (StudentInvoice $invoice): void {
            if (blank($invoice->invoice_number)) {
                $invoice->invoice_number = 'INV-'.now()->format('Ymd').'-'.str_pad((string) (static::query()->count() + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance' => 'decimal:2',
            'payment_metadata' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function studentDiscount(): BelongsTo
    {
        return $this->belongsTo(StudentDiscount::class);
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'income_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StudentInvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class, 'related_id')
            ->where('related_type', self::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class, 'transactionable_id')
            ->where('transactionable_type', self::class);
    }

    public function refreshAmounts(): void
    {
        $subtotal = (float) $this->items()->sum('amount');
        $discount = (float) ($this->discount ?? 0);
        $total = max($subtotal - $discount, 0);
        $amountPaid = (float) $this->payments()
            ->where('status', 'confirmed')
            ->sum('amount');
        $balance = max($total - $amountPaid, 0);

        $status = match (true) {
            $balance <= 0 && $total > 0 => 'paid',
            $amountPaid > 0 && $balance > 0 => 'partial',
            filled($this->due_date) && $this->due_date->isPast() && $balance > 0 => 'overdue',
            default => 'unpaid',
        };

        $this->forceFill([
            'subtotal' => $subtotal,
            'total' => $total,
            'amount_paid' => $amountPaid,
            'balance' => $balance,
            'status' => $this->status === 'cancelled' ? 'cancelled' : $status,
        ])->saveQuietly();
    }
}
