<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'student_invoice_id',
    'student_id',
    'guardian_id',
    'type',
    'channel',
    'recipient_contact',
    'message',
    'scheduled_for',
    'sent_at',
    'status',
    'metadata',
])]
class Reminder extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studentInvoice(): BelongsTo
    {
        return $this->belongsTo(StudentInvoice::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }
}
