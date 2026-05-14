<x-filament-panels::page>
    @if (! $staff)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            Your login is active, but it is not linked to a staff profile yet.
        </div>
    @else
        <div class="grid gap-5">
            <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-slate-900">
                <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Teacher</div>
                <div class="mt-1 text-2xl font-semibold text-slate-950 dark:text-white">{{ $staff->full_name }}</div>
                <div class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    {{ $staff->staff_number }}@if ($staff->job_title) · {{ $staff->job_title }} @endif
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-2">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-slate-950 dark:text-white">Form Teacher Duties</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Classes or arms assigned to you.</p>
                        </div>
                        <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700 dark:bg-teal-400/10 dark:text-teal-300">
                            {{ $formAssignments->count() }}
                        </span>
                    </div>

                    <div class="mt-4 divide-y divide-slate-100 dark:divide-white/10">
                        @forelse ($formAssignments as $assignment)
                            <div class="py-3">
                                <div class="font-medium text-slate-900 dark:text-white">
                                    {{ $assignment->schoolClass?->name }}
                                    @if ($assignment->classSection)
                                        {{ $assignment->classSection->name }}
                                    @endif
                                </div>
                                <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    {{ str($assignment->assignment_role)->replace('_', ' ')->title() }}
                                    · {{ $assignment->academicYear?->name ?? 'Session not set' }}
                                    @if ($assignment->term)
                                        · {{ $assignment->term->name }}
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-sm text-slate-500 dark:text-slate-400">
                                No form-teacher assignment has been added for you yet.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-slate-950 dark:text-white">Subject Teaching</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Subjects and classes assigned to you.</p>
                        </div>
                        <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700 dark:bg-teal-400/10 dark:text-teal-300">
                            {{ $subjectAssignments->count() }}
                        </span>
                    </div>

                    <div class="mt-4 divide-y divide-slate-100 dark:divide-white/10">
                        @forelse ($subjectAssignments as $assignment)
                            <div class="py-3">
                                <div class="font-medium text-slate-900 dark:text-white">
                                    {{ $assignment->subject?->name }}
                                </div>
                                <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    {{ $assignment->schoolClass?->name }}
                                    @if ($assignment->classSection)
                                        {{ $assignment->classSection->name }}
                                    @endif
                                    · {{ $assignment->academicYear?->name ?? 'Session not set' }}
                                    @if ($assignment->term)
                                        · {{ $assignment->term->name }}
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-sm text-slate-500 dark:text-slate-400">
                                No subject has been assigned to you yet.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    @endif
</x-filament-panels::page>
