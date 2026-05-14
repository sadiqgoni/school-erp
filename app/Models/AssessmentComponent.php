<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['school_id', 'exam_id', 'name', 'code', 'max_score', 'position', 'is_active'])]
class AssessmentComponent extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $component): void {
            if (blank($component->code)) {
                $component->code = str($component->name)
                    ->upper()
                    ->replaceMatches('/[^A-Z0-9]+/', '_')
                    ->trim('_')
                    ->limit(40, '')
                    ->toString() ?: 'COMPONENT';
            }
        });
    }

    protected function casts(): array
    {
        return [
            'max_score' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(StudentScore::class);
    }
}
