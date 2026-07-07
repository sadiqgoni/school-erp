<x-filament-panels::page>
    <div class="fee-debtors-page grid gap-4">
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Every invoice with money still owing. Use <span class="font-semibold text-emerald-600 dark:text-emerald-400">Remind on WhatsApp</span>
            to open a ready-made reminder message for the parent. Just press send.
        </p>

        <div class="responsive-table-shell fee-debtors-table-shell">
            <div class="responsive-table-shell__hint">Scroll sideways to see all columns.</div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
