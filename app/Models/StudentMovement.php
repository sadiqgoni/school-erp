<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'student_id',
    'student_device_id',
    'bus_route_id',
    'event_type',
    'happened_at',
    'source',
    'recorded_by',
    'notes',
])]
class StudentMovement extends Model
{
    public const EVENT_CHECK_IN = 'check_in';

    public const EVENT_CHECK_OUT = 'check_out';

    public const EVENT_BUS_BOARDED = 'bus_boarded';

    public const EVENT_BUS_DROPPED = 'bus_dropped';

    public const EVENTS = [
        self::EVENT_CHECK_IN => 'Arrived school',
        self::EVENT_CHECK_OUT => 'Left school',
        self::EVENT_BUS_BOARDED => 'Boarded bus',
        self::EVENT_BUS_DROPPED => 'Dropped off bus',
    ];

    protected function casts(): array
    {
        return [
            'happened_at' => 'datetime',
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

    public function device(): BelongsTo
    {
        return $this->belongsTo(StudentDevice::class, 'student_device_id');
    }

    public function busRoute(): BelongsTo
    {
        return $this->belongsTo(BusRoute::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function eventLabel(): string
    {
        return self::EVENTS[$this->event_type] ?? str($this->event_type)->replace('_', ' ')->title()->toString();
    }
}
