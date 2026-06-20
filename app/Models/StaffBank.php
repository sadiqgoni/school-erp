<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['school_id', 'name', 'code', 'is_active', 'notes'])]
class StaffBank extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (StaffBank $bank): void {
            if (blank($bank->code) && filled($bank->name)) {
                $bank->code = Str::upper(Str::slug($bank->name, '-'));
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

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
