<div class="space-y-4">
    @foreach ($sections as $section)
        @php
            $divisionSchool = $section['school'];
            $issues = $section['issues'];
        @endphp

        <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-base font-semibold text-slate-950 dark:text-white">{{ $section['label'] }}</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ $divisionSchool->code }} ·
                        <a href="{{ $divisionSchool->portalUrl() }}" target="_blank" class="text-primary-600 hover:underline dark:text-primary-400">
                            Open portal
                        </a>
                    </p>
                </div>

                <span @class([
                    'rounded-full px-2.5 py-1 text-xs font-semibold',
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' => count($issues) === 0,
                    'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' => count($issues) > 0 && count($issues) <= 2,
                    'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300' => count($issues) > 2,
                ])>
                    {{ count($issues) }} {{ Str::plural('issue', count($issues)) }}
                </span>
            </div>

            <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-md bg-slate-50 p-3 dark:bg-black/20">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Students</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-950 dark:text-white">{{ $section['active_students_count'] }}</dd>
                </div>
                <div class="rounded-md bg-slate-50 p-3 dark:bg-black/20">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Current term</dt>
                    <dd class="mt-1 text-sm font-semibold {{ $section['has_current_term'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                        {{ $section['has_current_term'] ? 'Set' : 'Missing' }}
                    </dd>
                </div>
                <div class="rounded-md bg-slate-50 p-3 dark:bg-black/20">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Fee setup</dt>
                    <dd class="mt-1 text-sm font-semibold {{ $section['has_fee_setup'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                        {{ $section['has_fee_setup'] ? 'Ready' : 'Missing' }}
                    </dd>
                </div>
                <div class="rounded-md bg-slate-50 p-3 dark:bg-black/20">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Admin</dt>
                    <dd class="mt-1 text-sm font-semibold {{ $section['has_admin'] ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                        {{ $section['has_admin'] ? 'Assigned' : 'Missing' }}
                    </dd>
                </div>
                <div class="rounded-md bg-slate-50 p-3 dark:bg-black/20">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">Failed messages</dt>
                    <dd class="mt-1 text-sm font-semibold {{ $section['failed_messages_count'] > 0 ? 'text-red-700 dark:text-red-300' : 'text-slate-950 dark:text-white' }}">
                        {{ $section['failed_messages_count'] }}
                    </dd>
                </div>
            </dl>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div>
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Open issues</h4>
                    @if (count($issues) === 0)
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">No open setup issues for this section.</p>
                    @else
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-700 dark:text-slate-300">
                            @foreach ($issues as $issue)
                                <li>{{ $issue }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Failed message details</h4>
                    @if ($section['failed_messages']->isEmpty())
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">No failed messages for this section.</p>
                    @else
                        <div class="mt-2 space-y-2">
                            @foreach ($section['failed_messages'] as $message)
                                <div class="rounded-md border border-red-100 bg-red-50 p-3 text-sm dark:border-red-500/20 dark:bg-red-500/10">
                                    <div class="font-semibold text-red-800 dark:text-red-200">
                                        {{ $message->recipient_name ?: 'Unknown recipient' }}
                                        <span class="font-normal text-red-600 dark:text-red-300">· {{ strtoupper($message->channel) }}</span>
                                    </div>
                                    <div class="mt-1 text-red-700 dark:text-red-200">
                                        {{ $message->failure_reason ?: 'No failure reason was saved.' }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endforeach
</div>
