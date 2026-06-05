<x-filament-widgets::widget>
    <section class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-slate-900">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $schoolName }} @if ($sectionName) · {{ $sectionName }} @endif</p>
                <h2 class="mt-1 text-xl font-semibold text-slate-950 dark:text-white">Parent Workspace</h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">View your linked children, school bills, payment status, and published results.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ $invoiceUrl }}" class="rounded-md bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700">
                    My Invoices
                </a>
                <a href="{{ $resultUrl }}" class="rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/5">
                    My Results
                </a>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-lg border border-slate-200 p-4 dark:border-white/10">
                <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Linked children</p>
                <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">{{ $childrenCount }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 p-4 dark:border-white/10">
                <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Unpaid invoices</p>
                <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">{{ $unpaidInvoices }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 p-4 dark:border-white/10">
                <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Outstanding balance</p>
                <p class="mt-2 text-2xl font-bold text-slate-950 dark:text-white">NGN {{ number_format($outstandingBalance, 2) }}</p>
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
