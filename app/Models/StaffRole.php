<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['school_id', 'name', 'code', 'description', 'permissions', 'is_active'])]
class StaffRole extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (StaffRole $staffRole): void {
            if (blank($staffRole->code) && filled($staffRole->name)) {
                $staffRole->code = Str::upper(Str::slug($staffRole->name, '-'));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(StaffRoleAssignment::class);
    }
}
