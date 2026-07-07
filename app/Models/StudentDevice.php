<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'school_id',
    'student_id',
    'identifier',
    'device_type',
    'label',
    'is_active',
])]
class StudentDevice extends Model
{
    public const TYPES = [
        'nfc_card' => 'NFC Card',
        'smart_watch' => 'Smart Watch',
        'wristband' => 'Wristband',
        'id_tag' => 'ID Tag',
    ];

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

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StudentMovement::class);
    }
}
