<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['school_id', 'name', 'code', 'description', 'is_active'])]
class BillingCategory extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (BillingCategory $category): void {
            if (blank($category->code) && filled($category->name)) {
                $category->code = Str::upper(Str::slug($category->name, '-'));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function feeTypes(): HasMany
    {
        return $this->hasMany(FeeType::class);
    }
}
