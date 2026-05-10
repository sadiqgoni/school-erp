<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_id', 'parent_id', 'code', 'name', 'type', 'opening_balance', 'is_system', 'is_active', 'description'])]
class LedgerAccount extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
    }

    public function getBalanceAttribute(): float
    {
        $debits = (float) $this->transactions()->where('direction', 'debit')->where('status', 'posted')->sum('amount');
        $credits = (float) $this->transactions()->where('direction', 'credit')->where('status', 'posted')->sum('amount');

        return (float) $this->opening_balance + $debits - $credits;
    }
}
