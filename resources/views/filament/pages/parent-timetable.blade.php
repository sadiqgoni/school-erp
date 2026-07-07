<x-filament-panels::page>
    @if ($schedules->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-white/10 dark:text-slate-400">
            No class placement found for your children yet, so there is no timetable to show.
        </div>
    @else
        <div class="grid gap-8">
            @foreach ($schedules as $schedule)
                <section class="grid gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-semibold text-slate-950 dark:text-white">
                            {{ $schedule['student']->full_name }}
                        </h2>
                        @if ($schedule['classLabel'])
                            <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700 dark:bg-teal-400/10 dark:text-teal-300">
                                {{ $schedule['classLabel'] }}
                            </span>
                        @endif
                    </div>

                    @include('filament.partials.timetable-grid', ['entries' => $schedule['entries']])
                </section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
