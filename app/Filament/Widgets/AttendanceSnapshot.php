<?php

namespace App\Filament\Widgets;

use App\Models\StaffAttendance;
use App\Models\StudentAttendanceRecord;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class AttendanceSnapshot extends ChartWidget
{
    protected ?string $heading = 'Attendance Snapshot';

    protected ?string $description = 'Present, late, absent, and leave records';

    protected string $color = 'info';

    protected function getData(): array
    {
        $tenant = Filament::getTenant();

        $studentAttendanceQuery = StudentAttendanceRecord::query();
        $staffAttendanceQuery = StaffAttendance::query();

        if ($tenant) {
            $studentAttendanceQuery->whereBelongsTo($tenant, 'school');
            $staffAttendanceQuery->whereBelongsTo($tenant, 'school');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Students',
                    'data' => [
                        (clone $studentAttendanceQuery)->where('status', 'present')->count(),
                        (clone $studentAttendanceQuery)->where('status', 'late')->count(),
                        (clone $studentAttendanceQuery)->where('status', 'absent')->count(),
                        (clone $studentAttendanceQuery)->whereIn('status', ['excused', 'sick'])->count(),
                    ],
                    'backgroundColor' => '#0f766e',
                ],
                [
                    'label' => 'Staff',
                    'data' => [
                        (clone $staffAttendanceQuery)->where('status', 'present')->count(),
                        (clone $staffAttendanceQuery)->where('status', 'late')->count(),
                        (clone $staffAttendanceQuery)->where('status', 'absent')->count(),
                        (clone $staffAttendanceQuery)->whereIn('status', ['on_leave', 'sick'])->count(),
                    ],
                    'backgroundColor' => '#2563eb',
                ],
            ],
            'labels' => ['Present', 'Late', 'Absent', 'Leave/Sick'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
