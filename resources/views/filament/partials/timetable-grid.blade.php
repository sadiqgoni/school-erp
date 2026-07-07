@php
    /** @var \Illuminate\Support\Collection $entries */
    $days = \App\Models\TimetableEntry::DAYS;

    $periods = $entries
        ->pluck('period_number')
        ->unique()
        ->sort()
        ->values();

    $byPeriodDay = $entries->groupBy(
        fn ($entry) => $entry->period_number.':'.$entry->day_of_week,
    );

    $timeForPeriod = fn (int $period) => $entries
        ->where('period_number', $period)
        ->map(fn ($entry) => $entry->timeRange())
        ->filter()
        ->first();

    $palette = [
        ['bg' => 'bg-teal-50 dark:bg-teal-400/10', 'text' => 'text-teal-800 dark:text-teal-200', 'ring' => 'ring-teal-600/20'],
        ['bg' => 'bg-sky-50 dark:bg-sky-400/10', 'text' => 'text-sky-800 dark:text-sky-200', 'ring' => 'ring-sky-600/20'],
        ['bg' => 'bg-violet-50 dark:bg-violet-400/10', 'text' => 'text-violet-800 dark:text-violet-200', 'ring' => 'ring-violet-600/20'],
        ['bg' => 'bg-rose-50 dark:bg-rose-400/10', 'text' => 'text-rose-800 dark:text-rose-200', 'ring' => 'ring-rose-600/20'],
        ['bg' => 'bg-emerald-50 dark:bg-emerald-400/10', 'text' => 'text-emerald-800 dark:text-emerald-200', 'ring' => 'ring-emerald-600/20'],
        ['bg' => 'bg-indigo-50 dark:bg-indigo-400/10', 'text' => 'text-indigo-800 dark:text-indigo-200', 'ring' => 'ring-indigo-600/20'],
        ['bg' => 'bg-orange-50 dark:bg-orange-400/10', 'text' => 'text-orange-800 dark:text-orange-200', 'ring' => 'ring-orange-600/20'],
        ['bg' => 'bg-cyan-50 dark:bg-cyan-400/10', 'text' => 'text-cyan-800 dark:text-cyan-200', 'ring' => 'ring-cyan-600/20'],
    ];

    $colorFor = fn ($entry) => $palette[($entry->subject_id ?? strlen((string) $entry->label)) % count($palette)];
@endphp

@if ($periods->isEmpty())
    <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-white/10 dark:text-slate-400">
        No timetable has been set up for this class yet.
    </div>
@else
    <div class="responsive-table-shell">
        <div class="responsive-table-shell__hint">Scroll sideways to view the full weekly grid.</div>
        <div class="responsive-table-scroll rounded-xl border border-slate-200 shadow-sm dark:border-white/10">
            <table class="responsive-data-table w-full min-w-[720px] border-collapse bg-white text-sm dark:bg-slate-900">
                <thead>
                    <tr class="bg-slate-50 dark:bg-white/5">
                        <th class="sticky left-0 z-10 w-32 border-b border-slate-200 bg-slate-50 px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-white/10 dark:bg-slate-900 dark:text-slate-400">
                            Period / Time
                        </th>
                        @foreach ($days as $dayName)
                            <th class="border-b border-slate-200 px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-white/10 dark:text-slate-400">
                                {{ $dayName }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($periods as $period)
                        <tr>
                            <td class="sticky left-0 z-10 border-b border-slate-100 bg-white px-3 py-2 align-top dark:border-white/5 dark:bg-slate-900">
                                <div class="font-semibold text-slate-900 dark:text-white">Period {{ $period }}</div>
                                @if ($timeForPeriod($period))
                                    <div class="mt-0.5 whitespace-nowrap text-xs font-medium text-slate-600 dark:text-slate-300">{{ $timeForPeriod($period) }}</div>
                                @endif
                            </td>
                            @foreach ($days as $dayNumber => $dayName)
                                @php
                                    $entry = $byPeriodDay->get($period.':'.$dayNumber)?->first();
                                @endphp
                                <td class="border-b border-slate-100 px-2 py-2 align-top dark:border-white/5">
                                    @if ($entry && $entry->entry_type === \App\Models\TimetableEntry::TYPE_BREAK)
                                        <div class="rounded-lg bg-amber-50 px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300">
                                            {{ $entry->displayLabel() }}
                                        </div>
                                    @elseif ($entry)
                                        @php $color = $colorFor($entry); @endphp
                                        <div class="rounded-lg px-3 py-2 ring-1 ring-inset {{ $color['bg'] }} {{ $color['ring'] }}">
                                            <div class="font-semibold {{ $color['text'] }}">{{ $entry->displayLabel() }}</div>
                                            @if ($entry->timeRange())
                                                <div class="mt-0.5 whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">{{ $entry->timeRange() }}</div>
                                            @endif
                                            @if ($entry->staff?->full_name)
                                                <div class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ $entry->staff->full_name }}</div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="rounded-lg border border-dashed border-slate-200 px-3 py-2 text-center text-xs text-slate-300 dark:border-white/5 dark:text-slate-600">
                                            —
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
