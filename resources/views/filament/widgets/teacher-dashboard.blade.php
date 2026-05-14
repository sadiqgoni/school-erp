<x-filament-widgets::widget>
    @once
        <style>
            .teacher-dash{display:grid;gap:1rem}.teacher-dash__hero{display:grid;gap:1.2rem;border:1px solid rgba(15,118,110,.18);border-radius:.75rem;background:linear-gradient(135deg,#0f766e,#164e63);padding:1.35rem;color:white;box-shadow:0 16px 34px rgba(15,23,42,.16)}@media (min-width:900px){.teacher-dash__hero{grid-template-columns:1fr auto;align-items:center}}.teacher-dash__eyebrow{font-size:.76rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#99f6e4}.teacher-dash__title{margin-top:.25rem;font-size:clamp(1.45rem,3vw,2.1rem);font-weight:850;line-height:1.1}.teacher-dash__meta{margin-top:.45rem;color:#d1fae5;font-size:.95rem}.teacher-dash__actions{display:flex;flex-wrap:wrap;gap:.55rem}.teacher-dash__action{display:inline-flex;align-items:center;gap:.45rem;border-radius:.5rem;background:rgba(255,255,255,.12);padding:.65rem .85rem;color:white;font-size:.9rem;font-weight:800;text-decoration:none}.teacher-dash__action:hover{background:rgba(255,255,255,.2)}.teacher-dash__grid{display:grid;grid-template-columns:repeat(1,minmax(0,1fr));gap:.85rem}@media (min-width:720px){.teacher-dash__grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media (min-width:1180px){.teacher-dash__grid{grid-template-columns:repeat(5,minmax(0,1fr))}}.teacher-dash__card{border:1px solid rgba(15,118,110,.14);border-radius:.65rem;background:white;padding:1rem;box-shadow:0 8px 18px rgba(15,23,42,.05)}.dark .teacher-dash__card{border-color:rgba(148,163,184,.18);background:rgba(15,23,42,.62)}.teacher-dash__card-top{display:flex;align-items:center;justify-content:space-between;gap:.75rem}.teacher-dash__icon{display:grid;height:2.25rem;width:2.25rem;place-items:center;border-radius:.5rem;background:#ecfdf5;color:#0f766e}.dark .teacher-dash__icon{background:rgba(45,212,191,.12);color:#5eead4}.teacher-dash__value{margin-top:.75rem;color:#020617;font-size:2rem;font-weight:850;line-height:1}.dark .teacher-dash__value{color:#f8fafc}.teacher-dash__label{color:#334155;font-size:.92rem;font-weight:800}.dark .teacher-dash__label{color:#e2e8f0}.teacher-dash__desc{margin-top:.45rem;color:#64748b;font-size:.82rem;line-height:1.35}.dark .teacher-dash__desc{color:#94a3b8}.teacher-dash__lists{display:grid;gap:1rem}@media (min-width:900px){.teacher-dash__lists{grid-template-columns:1fr 1fr}}.teacher-dash__panel{border:1px solid rgba(15,118,110,.12);border-radius:.7rem;background:white;padding:1rem;box-shadow:0 8px 18px rgba(15,23,42,.04)}.dark .teacher-dash__panel{border-color:rgba(148,163,184,.18);background:rgba(15,23,42,.55)}.teacher-dash__panel h3{color:#0f172a;font-size:1rem;font-weight:850}.dark .teacher-dash__panel h3{color:#f8fafc}.teacher-dash__item{display:flex;justify-content:space-between;gap:1rem;border-top:1px solid rgba(148,163,184,.18);padding:.75rem 0}.teacher-dash__item:first-of-type{border-top:0}.teacher-dash__item strong{color:#0f172a;font-size:.93rem}.dark .teacher-dash__item strong{color:#f8fafc}.teacher-dash__item span{color:#64748b;font-size:.82rem}.dark .teacher-dash__item span{color:#94a3b8}.teacher-dash__empty{padding:.9rem 0;color:#64748b;font-size:.9rem}.dark .teacher-dash__empty{color:#94a3b8}
        </style>
    @endonce

    <div class="teacher-dash">
        <section class="teacher-dash__hero">
            <div>
                <div class="teacher-dash__eyebrow">Teacher Portal</div>
                <div class="teacher-dash__title">
                    {{ $staff ? 'Welcome, '.$staff->full_name : 'Welcome' }}
                </div>
                <div class="teacher-dash__meta">
                    {{ $schoolName }}
                    @if ($staff?->staff_number)
                        · {{ $staff->staff_number }}
                    @endif
                    @if ($staff?->job_title)
                        · {{ $staff->job_title }}
                    @endif
                </div>
            </div>

            <div class="teacher-dash__actions">
                @foreach ($actions as $action)
                    <a class="teacher-dash__action" href="{{ $action['url'] }}">
                        <x-filament::icon :icon="$action['icon']" class="h-5 w-5" />
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </section>

        <section class="teacher-dash__grid">
            @foreach ($cards as $card)
                <article class="teacher-dash__card">
                    <div class="teacher-dash__card-top">
                        <span class="teacher-dash__label">{{ $card['label'] }}</span>
                        <span class="teacher-dash__icon">
                            <x-filament::icon :icon="$card['icon']" class="h-5 w-5" />
                        </span>
                    </div>
                    <div class="teacher-dash__value">{{ $card['value'] }}</div>
                    <div class="teacher-dash__desc">{{ $card['description'] }}</div>
                </article>
            @endforeach
        </section>

        <section class="teacher-dash__lists">
            <div class="teacher-dash__panel">
                <h3>Form Class Duties</h3>

                @forelse ($formAssignments as $assignment)
                    <div class="teacher-dash__item">
                        <div>
                            <strong>
                                {{ $assignment->schoolClass?->name }}
                                @if ($assignment->classSection)
                                    {{ $assignment->classSection->name }}
                                @endif
                            </strong>
                            <span>{{ str($assignment->assignment_role)->replace('_', ' ')->title() }}</span>
                        </div>
                        <span>{{ $assignment->academicYear?->name }}</span>
                    </div>
                @empty
                    <div class="teacher-dash__empty">No form class has been assigned yet.</div>
                @endforelse
            </div>

            <div class="teacher-dash__panel">
                <h3>Subject Teaching</h3>

                @forelse ($subjectAssignments as $assignment)
                    <div class="teacher-dash__item">
                        <div>
                            <strong>{{ $assignment->subject?->name }}</strong>
                            <span>
                                {{ $assignment->schoolClass?->name }}
                                @if ($assignment->classSection)
                                    {{ $assignment->classSection->name }}
                                @endif
                            </span>
                        </div>
                        <span>{{ $assignment->term?->name ?? 'All term' }}</span>
                    </div>
                @empty
                    <div class="teacher-dash__empty">No subject teaching load has been assigned yet.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-widgets::widget>
