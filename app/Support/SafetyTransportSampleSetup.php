<?php

namespace App\Support;

use App\Models\BusRoute;
use App\Models\BusRouteStudent;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentDevice;
use App\Models\StudentMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SafetyTransportSampleSetup
{
    /**
     * @return array{devices:int,routes:int,assignments:int,movements:int}
     */
    public static function createForSchool(School $school): array
    {
        return DB::transaction(function () use ($school): array {
            $students = Student::query()
                ->where('school_id', $school->getKey())
                ->where('status', 'active')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();

            if ($students->isEmpty()) {
                return [
                    'devices' => 0,
                    'routes' => 0,
                    'assignments' => 0,
                    'movements' => 0,
                ];
            }

            $school->forceFill([
                'device_api_token' => $school->device_api_token ?: self::gatewayToken($school),
            ])->saveQuietly();

            $devices = self::createDevices($school, $students);
            $routes = self::createRoutes($school);
            $assignments = self::assignStudentsToRoutes($school, $students, $routes);
            $movements = self::createMovements($school, $students, $devices, $routes, $assignments);

            return [
                'devices' => $devices->count(),
                'routes' => $routes->count(),
                'assignments' => $assignments->count(),
                'movements' => $movements,
            ];
        });
    }

    protected static function gatewayToken(School $school): string
    {
        return substr(hash('sha256', 'sample-gateway-'.$school->getKey().'-'.$school->slug), 0, 48);
    }

    /**
     * @param  Collection<int, Student>  $students
     * @return Collection<int, StudentDevice>
     */
    protected static function createDevices(School $school, Collection $students): Collection
    {
        return $students->values()->map(function (Student $student, int $index) use ($school): StudentDevice {
            $type = array_keys(StudentDevice::TYPES)[$index % count(StudentDevice::TYPES)];

            return StudentDevice::query()->updateOrCreate(
                [
                    'school_id' => $school->getKey(),
                    'identifier' => self::deviceIdentifier($school, $index + 1),
                ],
                [
                    'student_id' => $student->getKey(),
                    'device_type' => $type,
                    'label' => self::deviceLabel($type, $index),
                    'is_active' => true,
                ],
            );
        });
    }

    /**
     * @return Collection<int, BusRoute>
     */
    protected static function createRoutes(School $school): Collection
    {
        return collect(self::routeTemplates($school))
            ->map(function (array $template) use ($school): BusRoute {
                return BusRoute::query()->updateOrCreate(
                    [
                        'school_id' => $school->getKey(),
                        'code' => $template['code'],
                    ],
                    $template + [
                        'school_id' => $school->getKey(),
                        'is_active' => true,
                    ],
                );
            });
    }

    /**
     * @param  Collection<int, Student>  $students
     * @param  Collection<int, BusRoute>  $routes
     * @return Collection<int, BusRouteStudent>
     */
    protected static function assignStudentsToRoutes(School $school, Collection $students, Collection $routes): Collection
    {
        $pickupPoints = self::pickupPoints($school);

        return $students
            ->take(max(2, (int) ceil($students->count() * 0.7)))
            ->values()
            ->map(function (Student $student, int $index) use ($school, $routes, $pickupPoints): BusRouteStudent {
                $route = $routes[$index % max($routes->count(), 1)];
                $pickup = $pickupPoints[$index % count($pickupPoints)];

                return BusRouteStudent::query()->updateOrCreate(
                    [
                        'school_id' => $school->getKey(),
                        'bus_route_id' => $route->getKey(),
                        'student_id' => $student->getKey(),
                    ],
                    [
                        'pickup_point' => $pickup,
                        'drop_point' => $pickup,
                        'is_active' => true,
                    ],
                );
            });
    }

    /**
     * @param  Collection<int, Student>  $students
     * @param  Collection<int, StudentDevice>  $devices
     * @param  Collection<int, BusRoute>  $routes
     * @param  Collection<int, BusRouteStudent>  $assignments
     */
    protected static function createMovements(
        School $school,
        Collection $students,
        Collection $devices,
        Collection $routes,
        Collection $assignments,
    ): int {
        $assignmentMap = $assignments->keyBy('student_id');
        $deviceMap = $devices->keyBy('student_id');
        $now = now()->startOfDay();
        $count = 0;

        foreach ($students->values() as $index => $student) {
            $device = $deviceMap->get($student->getKey());
            $assignment = $assignmentMap->get($student->getKey());
            $route = $assignment ? $routes->firstWhere('id', $assignment->bus_route_id) : null;

            $events = [];

            if ($route) {
                $events[] = [
                    'event_type' => StudentMovement::EVENT_BUS_BOARDED,
                    'happened_at' => $now->copy()->addHours(6)->addMinutes(35 + ($index * 4)),
                    'bus_route_id' => $route->getKey(),
                    'notes' => 'Boarded at '.$assignment->pickup_point.'.',
                ];
            }

            $events[] = [
                'event_type' => StudentMovement::EVENT_CHECK_IN,
                'happened_at' => $now->copy()->addHours(7)->addMinutes(18 + ($index * 3)),
                'bus_route_id' => null,
                'notes' => 'Morning arrival scan recorded at the gate.',
            ];

            if ($index % 2 === 0) {
                $events[] = [
                    'event_type' => StudentMovement::EVENT_CHECK_OUT,
                    'happened_at' => $now->copy()->addHours(13)->addMinutes(5 + ($index * 2)),
                    'bus_route_id' => null,
                    'notes' => 'Afternoon exit confirmed by front desk.',
                ];

                if ($route) {
                    $events[] = [
                        'event_type' => StudentMovement::EVENT_BUS_DROPPED,
                        'happened_at' => $now->copy()->addHours(14)->addMinutes(10 + ($index * 3)),
                        'bus_route_id' => $route->getKey(),
                        'notes' => 'Dropped off near '.$assignment->drop_point.'.',
                    ];
                }
            }

            foreach ($events as $event) {
                StudentMovement::query()->updateOrCreate(
                    [
                        'school_id' => $school->getKey(),
                        'student_id' => $student->getKey(),
                        'event_type' => $event['event_type'],
                        'happened_at' => $event['happened_at'],
                    ],
                    [
                        'student_device_id' => $device?->getKey(),
                        'bus_route_id' => $event['bus_route_id'],
                        'source' => $device ? 'device' : 'manual',
                        'recorded_by' => null,
                        'notes' => $event['notes'],
                    ],
                );

                $count++;
            }
        }

        return $count;
    }

    protected static function deviceIdentifier(School $school, int $index): string
    {
        return sprintf('%s-NFC-%03d', strtoupper($school->code ?: 'SCH'), $index);
    }

    protected static function deviceLabel(string $type, int $index): string
    {
        return match ($type) {
            'smart_watch' => 'GPS watch '.($index + 1),
            'wristband' => 'Nursery wristband '.($index + 1),
            'id_tag' => 'School ID tag '.($index + 1),
            default => 'NFC card '.($index + 1),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function routeTemplates(School $school): array
    {
        $section = strtoupper((string) ($school->division ?: 'SCH'));

        return match ($school->division) {
            School::DIVISION_NURSERY => [
                [
                    'name' => 'Morning Nest Route',
                    'code' => "{$section}-BUS-1",
                    'vehicle_name' => 'Toyota Hiace Nursery Shuttle',
                    'plate_number' => 'ABC-241NT',
                    'driver_name' => 'Musa Danjuma',
                    'driver_phone' => '+2348032001101',
                    'assistant_name' => 'Aunty Bisi',
                    'assistant_phone' => '+2348032001102',
                    'capacity' => 18,
                ],
                [
                    'name' => 'Little Steps Route',
                    'code' => "{$section}-BUS-2",
                    'vehicle_name' => 'Coaster Early Years Bus',
                    'plate_number' => 'ABC-242NT',
                    'driver_name' => 'Tunde Ojo',
                    'driver_phone' => '+2348032001103',
                    'assistant_name' => 'Aunty Hauwa',
                    'assistant_phone' => '+2348032001104',
                    'capacity' => 24,
                ],
            ],
            default => [
                [
                    'name' => 'City Centre Route',
                    'code' => "{$section}-BUS-1",
                    'vehicle_name' => 'Toyota Hiace School Shuttle',
                    'plate_number' => 'ABC-341SD',
                    'driver_name' => 'Ibrahim Sule',
                    'driver_phone' => '+2348032002101',
                    'assistant_name' => 'Grace Obi',
                    'assistant_phone' => '+2348032002102',
                    'capacity' => 24,
                ],
                [
                    'name' => 'Estate Route',
                    'code' => "{$section}-BUS-2",
                    'vehicle_name' => 'Coaster School Bus',
                    'plate_number' => 'ABC-342SD',
                    'driver_name' => 'Sani Haruna',
                    'driver_phone' => '+2348032002103',
                    'assistant_name' => 'Mary James',
                    'assistant_phone' => '+2348032002104',
                    'capacity' => 30,
                ],
            ],
        };
    }

    /**
     * @return array<int, string>
     */
    protected static function pickupPoints(School $school): array
    {
        return match ($school->division) {
            School::DIVISION_NURSERY => [
                'Main Gate Estate',
                'GRA Junction',
                'Teachers Village',
                'Market Square Stop',
                'Custom Bridge',
            ],
            default => [
                'Central Mosque Stop',
                'Post Office Junction',
                'Government Quarters',
                'Unity Estate Gate',
                'Old Market Roundabout',
            ],
        };
    }
}
