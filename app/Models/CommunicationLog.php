<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'school_id',
    'student_id',
    'guardian_id',
    'user_id',
    'related_type',
    'related_id',
    'event_type',
    'channel',
    'direction',
    'recipient_name',
    'recipient_contact',
    'subject',
    'body',
    'status',
    'provider',
    'provider_message_id',
    'metadata',
    'sent_at',
    'delivered_at',
    'read_at',
    'failed_at',
    'failure_reason',
])]
class CommunicationLog extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
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

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
