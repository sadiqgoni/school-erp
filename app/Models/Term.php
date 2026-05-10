<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['school_id', 'academic_year_id', 'name', 'position', 'starts_on', 'ends_on', 'is_current', 'is_active'])]
class Term extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Term $term): void {
            if (filled($term->position)) {
                return;
            }

            $term->position = static::query()
                ->where('school_id', $term->school_id)
                ->where('academic_year_id', $term->academic_year_id)
                ->max('position') + 1;
        });
    }

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
