<x-filament-panels::page>
    <div class="grid gap-4">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            A quick checklist per school. Open a school to see each section separately, including the exact setup
            issues and any failed message reasons.
        </p>

        <div class="flex flex-wrap gap-2">
            @foreach ($this->healthTabs() as $key => $tab)
                <button
                    type="button"
                    wire:click="setHealthTab('{{ $key }}')"
                    @class([
                        'inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition',
                        'border-primary-600 bg-primary-50 text-primary-700 dark:border-primary-500 dark:bg-primary-500/10 dark:text-primary-300' => $this->healthTab === $key,
                        'border-slate-200 bg-white text-slate-600 hover:border-primary-300 hover:text-primary-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:border-primary-500' => $this->healthTab !== $key,
                    ])
                >
                    <span>{{ $tab['label'] }}</span>
                    <span @class([
                        'rounded-full px-2 py-0.5 text-xs',
                        'bg-primary-600 text-white dark:bg-primary-500' => $this->healthTab === $key,
                        'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300' => $this->healthTab !== $key,
                    ])>{{ $tab['count'] }}</span>
                </button>
            @endforeach
        </div>

        <div class="responsive-table-shell school-health-table-shell">
            <div class="responsive-table-shell__hint">Scroll sideways to see all columns.</div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
