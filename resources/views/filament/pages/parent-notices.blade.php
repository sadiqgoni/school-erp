<x-filament-panels::page>
    @if ($notices->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-white/10 dark:text-slate-400">
            No notices from the school yet. New announcements will appear here.
        </div>
    @else
        <div class="grid gap-4">
            @foreach ($notices as $notice)
                <article class="rounded-xl border {{ $notice->is_pinned ? 'border-amber-300 dark:border-amber-400/40' : 'border-slate-200 dark:border-white/10' }} bg-white p-5 shadow-sm dark:bg-slate-900">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($notice->is_pinned)
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-400/10 dark:text-amber-300">
                                ★ Pinned
                            </span>
                        @endif
                        <span @class([
                            'rounded-full px-2.5 py-0.5 text-xs font-semibold',
                            'bg-red-50 text-red-700 dark:bg-red-400/10 dark:text-red-300' => $notice->category === 'urgent',
                            'bg-orange-50 text-orange-700 dark:bg-orange-400/10 dark:text-orange-300' => $notice->category === 'fees',
                            'bg-sky-50 text-sky-700 dark:bg-sky-400/10 dark:text-sky-300' => $notice->category === 'event',
                            'bg-teal-50 text-teal-700 dark:bg-teal-400/10 dark:text-teal-300' => $notice->category === 'newsletter',
                            'bg-slate-100 text-slate-600 dark:bg-white/5 dark:text-slate-300' => ! in_array($notice->category, ['urgent', 'fees', 'event', 'newsletter'], true),
                        ])>
                            {{ ucfirst($notice->category) }}
                        </span>
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 dark:bg-white/5 dark:text-slate-300">
                            {{ $notice->audienceLabel() }}
                        </span>
                        <span class="ml-auto text-xs text-slate-400 dark:text-slate-500">
                            {{ $notice->published_at?->format('d M Y') }}
                        </span>
                    </div>

                    <h2 class="mt-3 text-base font-semibold text-slate-950 dark:text-white">
                        {{ $notice->title }}
                    </h2>

                    @if (filled($notice->body))
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $notice->body }}</p>
                    @endif

                    @if (filled($notice->attachment_path))
                        <a
                            href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($notice->attachment_path) }}"
                            target="_blank"
                            class="mt-4 inline-flex items-center gap-2 rounded-lg bg-teal-600 px-3 py-2 text-xs font-semibold text-white hover:bg-teal-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            Download newsletter
                        </a>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
