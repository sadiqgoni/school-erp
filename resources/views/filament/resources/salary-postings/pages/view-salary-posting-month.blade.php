<x-filament-panels::page>
    @once
        <style>
            .salary-folders{display:grid;gap:1rem}.salary-folders__hero{display:grid;gap:1rem;border:1px solid rgba(14,116,144,.16);border-radius:.85rem;background:linear-gradient(135deg,#0f766e,#1d4ed8);padding:1.2rem;color:#fff}.salary-folders__eyebrow{font-size:.75rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#bfdbfe}.salary-folders__title{font-size:clamp(1.4rem,2.5vw,2rem);font-weight:850;line-height:1.05}.salary-folders__stats{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}@media(min-width:960px){.salary-folders__stats{grid-template-columns:repeat(5,minmax(0,1fr))}}.salary-folders__stat{border-radius:.7rem;background:rgba(255,255,255,.12);padding:.85rem}.salary-folders__stat span{display:block;font-size:.74rem;font-weight:800;text-transform:uppercase;color:#dbeafe}.salary-folders__stat strong{display:block;margin-top:.2rem;font-size:1.2rem;font-weight:850}.salary-folders__grid{display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(280px,1fr))}.salary-folders__card{display:grid;gap:.9rem;padding:1.05rem;border:1px solid rgba(148,163,184,.2);border-radius:.85rem;background:#fff;box-shadow:0 10px 24px rgba(15,23,42,.05)}.dark .salary-folders__card{background:rgba(15,23,42,.6);border-color:rgba(148,163,184,.16)}.salary-folders__top{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start}.salary-folders__folder{display:flex;gap:.8rem;align-items:flex-start}.salary-folders__icon{display:grid;place-items:center;width:2.7rem;height:2.7rem;border-radius:.75rem;background:#eff6ff;color:#2563eb}.dark .salary-folders__icon{background:rgba(37,99,235,.14);color:#93c5fd}.salary-folders__name{font-size:1rem;font-weight:850;color:#0f172a}.dark .salary-folders__name{color:#f8fafc}.salary-folders__meta{margin-top:.2rem;color:#64748b;font-size:.82rem}.dark .salary-folders__meta{color:#94a3b8}.salary-folders__badge{border-radius:999px;background:#ecfdf5;color:#047857;padding:.28rem .7rem;font-size:.76rem;font-weight:800}.dark .salary-folders__badge{background:rgba(16,185,129,.14);color:#86efac}.salary-folders__totals{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem}.salary-folders__total{border-radius:.7rem;background:#f8fafc;padding:.7rem .8rem}.dark .salary-folders__total{background:rgba(2,6,23,.3)}.salary-folders__total span{display:block;font-size:.72rem;font-weight:800;text-transform:uppercase;color:#64748b}.dark .salary-folders__total span{color:#94a3b8}.salary-folders__total strong{display:block;margin-top:.15rem;font-size:.95rem;font-weight:800;color:#0f172a}.dark .salary-folders__total strong{color:#f8fafc}.salary-folders__actions{display:flex;gap:.6rem;justify-content:flex-end}
            .salary-folders__total--basic{background:#eff6ff}.salary-folders__total--basic strong{color:#1d4ed8}.salary-folders__total--earnings{background:#ecfdf5}.salary-folders__total--earnings strong{color:#047857}.salary-folders__total--deductions{background:#fef2f2}.salary-folders__total--deductions strong{color:#b91c1c}.salary-folders__total--net{background:#f0fdf4}.salary-folders__total--net strong{color:#166534}
        </style>
    @endonce

    <div class="salary-folders">
        <section class="salary-folders__hero">
            <div>
                <div class="salary-folders__eyebrow">Salary Posting</div>
                <div class="salary-folders__title">{{ $monthLabel }}</div>
            </div>

            <div class="salary-folders__stats">
                <div class="salary-folders__stat">
                    <span>Staff</span>
                    <strong>{{ number_format($summary['staff_count']) }}</strong>
                </div>
                <div class="salary-folders__stat">
                    <span>Basic</span>
                    <strong>NGN {{ number_format($summary['basic_total'], 2) }}</strong>
                </div>
                <div class="salary-folders__stat">
                    <span>Earnings</span>
                    <strong>NGN {{ number_format($summary['earnings_total'], 2) }}</strong>
                </div>
                <div class="salary-folders__stat">
                    <span>Deductions</span>
                    <strong>NGN {{ number_format($summary['deductions_total'], 2) }}</strong>
                </div>
                <div class="salary-folders__stat">
                    <span>Net Pay</span>
                    <strong>NGN {{ number_format($summary['net_total'], 2) }}</strong>
                </div>
            </div>
        </section>

        <div class="salary-folders__grid">
            @forelse ($sheets as $item)
                <article class="salary-folders__card">
                    <div class="salary-folders__top">
                        <div class="salary-folders__folder">
                            <div class="salary-folders__icon">
                                <x-filament::icon icon="heroicon-o-folder" class="h-6 w-6" />
                            </div>
                            <div>
                                <div class="salary-folders__name">{{ $item['sheet']->name }}</div>
                                <div class="salary-folders__meta">{{ number_format($item['staff_count']) }} staff in this sheet</div>
                            </div>
                        </div>
                        <div class="salary-folders__badge">Posted</div>
                    </div>

                    <div class="salary-folders__totals">
                        <div class="salary-folders__total salary-folders__total--basic">
                            <span>Basic</span>
                            <strong>NGN {{ number_format($item['basic_total'], 2) }}</strong>
                        </div>
                        <div class="salary-folders__total salary-folders__total--earnings">
                            <span>Earnings</span>
                            <strong>NGN {{ number_format($item['earnings_total'], 2) }}</strong>
                        </div>
                        <div class="salary-folders__total salary-folders__total--deductions">
                            <span>Deductions</span>
                            <strong>NGN {{ number_format($item['deductions_total'], 2) }}</strong>
                        </div>
                        <div class="salary-folders__total salary-folders__total--net">
                            <span>Net Pay</span>
                            <strong>NGN {{ number_format($item['net_total'], 2) }}</strong>
                        </div>
                    </div>

                    <div class="salary-folders__actions">
                        <x-filament::button
                            tag="a"
                            color="primary"
                            :href="\App\Filament\Resources\SalaryPostings\SalaryPostingResource::getUrl('sheet', ['month' => request()->route('month'), 'sheet' => $item['sheet']->getKey()])"
                        >
                            Open Sheet
                        </x-filament::button>
                    </div>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
                    No payroll sheet has posted staff for this month yet.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
