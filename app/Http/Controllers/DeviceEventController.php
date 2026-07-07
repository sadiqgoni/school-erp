<?php

namespace App\Http\Controllers;

use App\Models\BusRoute;
use App\Models\School;
use App\Models\StudentDevice;
use App\Models\StudentMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives scan events from NFC/watch gateways.
 *
 * POST /devices/events
 * { "token": "...", "device": "<identifier>", "event": "check_in|check_out|bus_boarded|bus_dropped",
 *   "happened_at": "2026-07-06T07:45:00", "bus_route": "<code>" }
 *
 * "event" is optional: without it the scan toggles between arrival and exit,
 * so simple gate readers only need to send the card identifier.
 */
class DeviceEventController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'device' => ['required', 'string'],
            'event' => ['nullable', 'string', 'in:check_in,check_out,bus_boarded,bus_dropped'],
            'happened_at' => ['nullable', 'date'],
            'bus_route' => ['nullable', 'string'],
        ]);

        $school = School::query()
            ->withoutGlobalScopes()
            ->where('device_api_token', $data['token'])
            ->first();

        if (! $school) {
            return response()->json(['message' => 'Invalid device token.'], 401);
        }

        $device = StudentDevice::query()
            ->with('student')
            ->where('school_id', $school->getKey())
            ->where('identifier', $data['device'])
            ->where('is_active', true)
            ->first();

        if (! $device || ! $device->student) {
            return response()->json(['message' => 'Unknown or inactive device.'], 404);
        }

        $eventType = $data['event'] ?? $this->toggleEventFor($device);

        $busRoute = null;

        if (! empty($data['bus_route'])) {
            $busRoute = BusRoute::query()
                ->where('school_id', $school->getKey())
                ->where(fn ($query) => $query
                    ->where('code', $data['bus_route'])
                    ->orWhere('name', $data['bus_route']))
                ->first();
        }

        $movement = StudentMovement::query()->create([
            'school_id' => $school->getKey(),
            'student_id' => $device->student_id,
            'student_device_id' => $device->getKey(),
            'bus_route_id' => $busRoute?->getKey(),
            'event_type' => $eventType,
            'happened_at' => $data['happened_at'] ?? now(),
            'source' => 'device',
        ]);

        return response()->json([
            'status' => 'ok',
            'student' => $device->student->full_name,
            'event' => $movement->eventLabel(),
            'happened_at' => $movement->happened_at->toIso8601String(),
        ]);
    }

    protected function toggleEventFor(StudentDevice $device): string
    {
        $lastToday = StudentMovement::query()
            ->where('student_id', $device->student_id)
            ->whereDate('happened_at', today())
            ->whereIn('event_type', [StudentMovement::EVENT_CHECK_IN, StudentMovement::EVENT_CHECK_OUT])
            ->latest('happened_at')
            ->first();

        return $lastToday?->event_type === StudentMovement::EVENT_CHECK_IN
            ? StudentMovement::EVENT_CHECK_OUT
            : StudentMovement::EVENT_CHECK_IN;
    }
}
