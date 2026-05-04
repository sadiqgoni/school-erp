<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'name',
    'code',
    'slug',
    'email',
    'phone',
    'address',
    'city',
    'state',
    'country',
    'logo_path',
    'primary_color',
    'subscription_plan',
    'subscription_expires_at',
    'student_limit',
    'enabled_modules',
    'is_active',
])]
class School extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'enabled_modules' => 'array',
            'is_active' => 'boolean',
            'subscription_expires_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'is_primary'])
            ->withTimestamps();
    }

    protected function slug(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value, array $attributes) => $value ?: Str::slug($attributes['name'] ?? ''),
        );
    }
}
