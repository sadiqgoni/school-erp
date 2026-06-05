<x-filament-panels::page>
    @if ($students->isEmpty())
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            Your parent login is active, but no child has been linked to this account yet.
        </div>
    @else
        <div class="grid gap-5">
            <div class="flex flex-wrap gap-2">
                <a href="{{ $invoiceUrl }}" class="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700">
                    My Invoices
                </a>
                <a href="{{ $resultUrl }}" class="rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5">
                    My Results
                </a>
            </div>

            @foreach ($students as $student)
                @php
                    $placement = $student->enrollments->sortByDesc('enrolled_on')->first();
                @endphp

                <section class="rounded-lg border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-900">
                    <div class="flex flex-wrap items-start justify-between gap-3 p-5">
                        <div>
                            <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ $student->full_name }}</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                {{ $student->admission_number }}
                                @if ($placement)
                                    · {{ $placement->schoolClass?->name }}@if ($placement->classSection) {{ $placement->classSection->name }}@endif
                                    · {{ $placement->academicYear?->name }}
                                @endif
                            </p>
                        </div>
                        <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700 dark:bg-teal-400/10 dark:text-teal-300">
                            {{ str($student->status)->title() }}
                        </span>
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
