<?php

namespace App\Filament\Pages;

use App\Models\BusRouteStudent;
use App\Models\Student;
use App\Models\StudentMovement;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ParentWhereabouts extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Whereabouts';

    protected static ?string $title = 'My Children\'s Whereabouts';

    protected static string|\UnitEnum|null $navigationGroup = 'Parent Portal';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.parent-whereabouts';

    public static function canAccess(): bool
    {
        return (bool) Filament::auth()->user()?->hasSchoolRole(Filament::getTenant(), 'parent');
    }

    protected function getViewData(): array
    {
        $tenant = Filament::getTenant();

        $children = Student::query()
            ->where('school_id', $tenant?->getKey())
            ->whereHas('guardianLinks.guardian', fn (Builder $query) => $query
                ->where('user_id', Filament::auth()->id()))
            ->get();

        $cards = $children->map(function (Student $student) use ($tenant): array {
            $todayLatest = StudentMovement::query()
                ->with('busRoute')
                ->where('school_id', $tenant?->getKey())
                ->where('student_id', $student->getKey())
                ->whereDate('happened_at', today())
                ->latest('happened_at')
                ->first();

            $recent = StudentMovement::query()
                ->with('busRoute')
                ->where('school_id', $tenant?->getKey())
                ->where('student_id', $student->getKey())
                ->latest('happened_at')
                ->limit(10)
                ->get();

            $busAssignment = BusRouteStudent::query()
                ->with('busRoute')
                ->where('student_id', $student->getKey())
                ->where('is_active', true)
                ->first();

            return [
                'student' => $student,
                'status' => $this->statusFor($todayLatest),
                'recent' => $recent,
                'busAssignment' => $busAssignment,
            ];
        });

        return [
            'cards' => $cards,
        ];
    }

    /**
     * @return array{label: string, detail: ?string, tone: string}
     */
    protected function statusFor(?StudentMovement $movement): array
    {
        if (! $movement) {
            return [
                'label' => 'No scan today',
                'detail' => 'No arrival has been recorded yet today.',
                'tone' => 'gray',
            ];
        }

        $time = $movement->happened_at->format('h:i A');

        return match ($movement->event_type) {
            StudentMovement::EVENT_CHECK_IN => [
                'label' => 'In school',
                'detail' => 'Arrived at '.$time,
                'tone' => 'green',
            ],
            StudentMovement::EVENT_CHECK_OUT => [
                'label' => 'Left school',
                'detail' => 'Signed out at '.$time,
                'tone' => 'amber',
            ],
            StudentMovement::EVENT_BUS_BOARDED => [
                'label' => 'On the school bus',
                'detail' => ($movement->busRoute?->name ? $movement->busRoute->name.' · ' : '').'boarded at '.$time,
                'tone' => 'blue',
            ],
            StudentMovement::EVENT_BUS_DROPPED => [
                'label' => 'Dropped off by bus',
                'detail' => ($movement->busRoute?->name ? $movement->busRoute->name.' · ' : '').'dropped at '.$time,
                'tone' => 'gray',
            ],
            default => [
                'label' => $movement->eventLabel(),
                'detail' => $time,
                'tone' => 'gray',
            ],
        };
    }
}
