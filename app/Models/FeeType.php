<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['school_id', 'name', 'code', 'description', 'is_required', 'is_active'])]
class FeeType extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (FeeType $feeType): void {
            if (blank($feeType->code) && filled($feeType->name)) {
                $feeType->code = Str::upper(Str::slug($feeType->name, '-'));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }
}
