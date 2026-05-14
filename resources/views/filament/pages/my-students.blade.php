<x-filament-panels::page>
    @if (! $staff)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            Your login is active, but it is not linked to a staff profile yet.
        </div>
    @elseif ($assignments->isEmpty())
        <div class="rounded-lg border border-slate-200 bg-white p-5 text-sm text-slate-600 shadow-sm dark:border-white/10 dark:bg-slate-900 dark:text-slate-300">
            You are not assigned as a form teacher yet.
        </div>
    @else
        <div class="grid gap-5">
            @foreach ($studentsByClass as $group)
                <section class="rounded-lg border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 p-5 dark:border-white/10">
                        <div>
                            <h2 class="text-base font-semibold text-slate-950 dark:text-white">{{ $group['label'] }}</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                {{ $group['session'] ?? 'Session not set' }}@if ($group['term']) · {{ $group['term'] }} @endif
                            </p>
                        </div>
                        <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700 dark:bg-teal-400/10 dark:text-teal-300">
                            {{ $group['enrollments']->count() }} student(s)
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-white/10">
                            <thead>
                                <tr class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-white/5 dark:text-slate-400">
                                    <th class="px-5 py-3">Student</th>
                                    <th class="px-5 py-3">Admission No.</th>
                                    <th class="px-5 py-3">Gender</th>
                                    <th class="px-5 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-white/10">
                                @forelse ($group['enrollments'] as $enrollment)
                                    <tr>
                                        <td class="px-5 py-3 font-medium text-slate-900 dark:text-white">
                                            {{ $enrollment->student?->full_name }}
                                        </td>
                                        <td class="px-5 py-3 text-slate-600 dark:text-slate-300">
                                            {{ $enrollment->student?->admission_number }}
                                        </td>
                                        <td class="px-5 py-3 text-slate-600 dark:text-slate-300">
                                            {{ str($enrollment->student?->gender)->title() }}
                                        </td>
                                        <td class="px-5 py-3 text-slate-600 dark:text-slate-300">
                                            {{ str($enrollment->student?->status)->title() }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">
                                            No active students found for this class.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
