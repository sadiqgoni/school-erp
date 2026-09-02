<x-filament-panels::page>
    <div class="mx-auto max-w-xl">
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
            Choose which section you'd like to work in.
        </p>

        <div class="grid gap-3">
            @foreach ($divisions as $division)
                <button
                    type="button"
                    wire:click="select({{ $division->id }})"
                    class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-primary-400 hover:shadow dark:border-white/10 dark:bg-slate-900"
                >
                    <span class="text-sm font-semibold text-slate-950 dark:text-white">
                        {{ $division->divisionLabel() ?? $division->name }}
                    </span>

                    <x-filament::icon
                        icon="heroicon-o-chevron-right"
                        class="h-4 w-4 text-slate-400"
                    />
                </button>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
