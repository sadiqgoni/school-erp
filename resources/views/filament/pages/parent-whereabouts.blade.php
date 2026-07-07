<x-filament-panels::page>
    @if ($cards->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-white/10 dark:text-slate-400">
            No child is linked to your account yet.
        </div>
    @else
        <div class="grid gap-5">
            @foreach ($cards as $card)
                @php
                    $toneClasses = match ($card['status']['tone']) {
                        'green' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-300',
                        'amber' => 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-300',
                        'blue' => 'bg-sky-50 text-sky-800 ring-sky-600/20 dark:bg-sky-400/10 dark:text-sky-300',
                        default => 'bg-slate-100 text-slate-600 ring-slate-500/20 dark:bg-white/5 dark:text-slate-300',
                    };
                    $dotClasses = match ($card['status']['tone']) {
                        'green' => 'bg-emerald-500',
                        'amber' => 'bg-amber-500',
                        'blue' => 'bg-sky-500',
                        default => 'bg-slate-400',
                    };
                @endphp

                <section class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-900">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 dark:border-white/5">
                        <div>
                            <h2 class="text-base font-semibold text-slate-950 dark:text-white">
                                {{ $card['student']->full_name }}
                            </h2>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                {{ $card['student']->admission_number }}
                            </p>
                        </div>

                        <span class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-semibold ring-1 ring-inset {{ $toneClasses }}">
                            <span class="relative flex h-2.5 w-2.5">
                                @if ($card['status']['tone'] === 'green')
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-60 {{ $dotClasses }}"></span>
                                @endif
                                <span class="relative inline-flex h-2.5 w-2.5 rounded-full {{ $dotClasses }}"></span>
                            </span>
                            {{ $card['status']['label'] }}
                        </span>
                    </div>

                    <div class="grid gap-5 p-5 sm:grid-cols-2">
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Today</h3>
                            <p class="mt-2 text-sm text-slate-700 dark:text-slate-200">{{ $card['status']['detail'] }}</p>

                            @if ($card['busAssignment'])
                                <div class="mt-4 rounded-lg bg-slate-50 p-3 text-sm dark:bg-white/5">
                                    <div class="font-semibold text-slate-800 dark:text-slate-100">
                                        🚌 {{ $card['busAssignment']->busRoute?->name }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        @if ($card['busAssignment']->busRoute?->driver_name)
                                            Driver: {{ $card['busAssignment']->busRoute->driver_name }}
                                            @if ($card['busAssignment']->busRoute->driver_phone)
                                                · {{ $card['busAssignment']->busRoute->driver_phone }}
                                            @endif
                                            <br>
                                        @endif
                                        @if ($card['busAssignment']->pickup_point)
                                            Pickup: {{ $card['busAssignment']->pickup_point }}
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Recent activity</h3>
                            @if ($card['recent']->isEmpty())
                                <p class="mt-2 text-sm text-slate-400 dark:text-slate-500">Nothing recorded yet.</p>
                            @else
                                <ol class="mt-2 space-y-2">
                                    @foreach ($card['recent'] as $movement)
                                        <li class="flex items-baseline gap-2 text-sm">
                                            <span class="whitespace-nowrap font-mono text-xs text-slate-400 dark:text-slate-500">
                                                {{ $movement->happened_at->format('d M · h:i A') }}
                                            </span>
                                            <span class="text-slate-700 dark:text-slate-200">
                                                {{ $movement->eventLabel() }}@if ($movement->busRoute) ({{ $movement->busRoute->name }})@endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
