<x-filament-panels::page>
    <div class="grid gap-5">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Class</label>
                <select
                    wire:model.live="classId"
                    class="rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-white/10 dark:bg-slate-900 dark:text-white"
                >
                    @foreach ($classOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            @if (! empty($sectionOptions))
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Arm</label>
                    <select
                        wire:model.live="sectionId"
                        class="rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-white/10 dark:bg-slate-900 dark:text-white"
                    >
                        <option value="">Whole class</option>
                        @foreach ($sectionOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($classLabel)
                <span class="mb-1 rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700 dark:bg-teal-400/10 dark:text-teal-300">
                    {{ $classLabel }} · weekly timetable
                </span>
            @endif
        </div>

        @include('filament.partials.timetable-grid', ['entries' => $entries])
    </div>
</x-filament-panels::page>
