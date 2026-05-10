<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'guardian_id',
    'student_id',
    'relationship',
    'is_primary_contact',
    'can_pick_up',
    'receives_sms',
    'notes',
])]
class GuardianStudent extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_primary_contact' => 'boolean',
            'can_pick_up' => 'boolean',
            'receives_sms' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
