<x-filament-panels::page>
    @once
        <style>
            .teaching-page{display:grid;gap:1rem}.teaching-page__hero{display:grid;gap:1.25rem;border-radius:.85rem;border:1px solid rgba(15,118,110,.18);background:linear-gradient(135deg,#0f766e,#155e75);padding:1.25rem;color:#fff;box-shadow:0 16px 34px rgba(15,23,42,.14)}@media (min-width:900px){.teaching-page__hero{grid-template-columns:1fr auto;align-items:center}}.teaching-page__eyebrow{color:#99f6e4;font-size:.74rem;font-weight:850;letter-spacing:.12em;text-transform:uppercase}.teaching-page__title{margin-top:.25rem;font-size:clamp(1.45rem,3vw,2.2rem);font-weight:850;line-height:1.05}.teaching-page__meta{margin-top:.5rem;color:#d1fae5;font-size:.93rem}.teaching-page__stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.55rem;min-width:min(100%,26rem)}.teaching-page__stat{border-radius:.6rem;background:rgba(255,255,255,.12);padding:.8rem}.teaching-page__stat span{display:block;color:#ccfbf1;font-size:.74rem;font-weight:800;text-transform:uppercase}.teaching-page__stat strong{display:block;margin-top:.25rem;font-size:1.55rem;font-weight:850}.teaching-page__grid{display:grid;gap:1rem}@media (min-width:1000px){.teaching-page__grid{grid-template-columns:.95fr 1.35fr}}.teaching-page__panel{border:1px solid rgba(15,118,110,.14);border-radius:.75rem;background:#fff;padding:1rem;box-shadow:0 8px 20px rgba(15,23,42,.05)}.dark .teaching-page__panel{border-color:rgba(148,163,184,.18);background:rgba(15,23,42,.58)}.teaching-page__panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:.75rem}.teaching-page__panel h2{color:#0f172a;font-size:1rem;font-weight:850}.dark .teaching-page__panel h2{color:#f8fafc}.teaching-page__panel p{margin-top:.2rem;color:#64748b;font-size:.84rem}.dark .teaching-page__panel p{color:#94a3b8}.teaching-page__badge{border-radius:999px;background:#ecfdf5;padding:.25rem .65rem;color:#0f766e;font-size:.78rem;font-weight:850}.dark .teaching-page__badge{background:rgba(45,212,191,.12);color:#5eead4}.teaching-page__form-card{display:grid;gap:.65rem;border-top:1px solid rgba(148,163,184,.18);padding:.85rem 0}.teaching-page__form-card:first-of-type{border-top:0}.teaching-page__class{display:flex;align-items:center;gap:.65rem}.teaching-page__icon{display:grid;height:2.4rem;width:2.4rem;place-items:center;border-radius:.6rem;background:#f0fdfa;color:#0f766e}.dark .teaching-page__icon{background:rgba(45,212,191,.12);color:#5eead4}.teaching-page__class strong{display:block;color:#0f172a;font-size:1rem;font-weight:850}.dark .teaching-page__class strong{color:#f8fafc}.teaching-page__class span,.teaching-page__detail{color:#64748b;font-size:.82rem}.dark .teaching-page__class span,.dark .teaching-page__detail{color:#94a3b8}.teaching-page__subjects{display:grid;gap:.75rem}.teaching-page__subject{border:1px solid rgba(15,118,110,.12);border-radius:.7rem;background:#f8fafc;padding:.85rem}.dark .teaching-page__subject{border-color:rgba(148,163,184,.16);background:rgba(2,6,23,.26)}.teaching-page__subject-top{display:flex;align-items:center;justify-content:space-between;gap:.8rem}.teaching-page__subject-name{color:#0f172a;font-size:.98rem;font-weight:850}.dark .teaching-page__subject-name{color:#f8fafc}.teaching-page__chips{display:flex;flex-wrap:wrap;gap:.45rem;margin-top:.65rem}.teaching-page__chip{border-radius:999px;border:1px solid rgba(15,118,110,.16);background:white;padding:.32rem .65rem;color:#334155;font-size:.78rem;font-weight:750}.dark .teaching-page__chip{border-color:rgba(148,163,184,.22);background:rgba(15,23,42,.7);color:#cbd5e1}.teaching-page__empty{border:1px dashed rgba(15,118,110,.25);border-radius:.7rem;padding:1rem;color:#64748b;font-size:.9rem}.dark .teaching-page__empty{color:#94a3b8}
        </style>
    @endonce

    @if (! $staff)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            Your login is active, but it is not linked to a staff profile yet.
        </div>
    @else
        <div class="teaching-page">
            <section class="teaching-page__hero">
                <div>
                    <div class="teaching-page__eyebrow">Teaching Profile</div>
                    <div class="teaching-page__title">{{ $staff->full_name }}</div>
                    <div class="teaching-page__meta">
                        {{ $staff->staff_number }}
                        @if ($staff->job_title)
                            · {{ $staff->job_title }}
                        @endif
                        @if ($staff->course_specialization)
                            · {{ $staff->course_specialization }}
                        @endif
                    </div>
                </div>

                <div class="teaching-page__stats">
                    <div class="teaching-page__stat">
                        <span>Form duties</span>
                        <strong>{{ $formAssignments->count() }}</strong>
                    </div>
                    <div class="teaching-page__stat">
                        <span>Subjects</span>
                        <strong>{{ $subjectGroups->count() }}</strong>
                    </div>
                    <div class="teaching-page__stat">
                        <span>Loads</span>
                        <strong>{{ $subjectAssignments->count() }}</strong>
                    </div>
                </div>
            </section>

            <div class="teaching-page__grid">
                <section class="teaching-page__panel">
                    <div class="teaching-page__panel-head">
                        <div>
                            <h2>Form Class Duties</h2>
                            <p>Classes or arms where you serve as class teacher.</p>
                        </div>
                        <span class="teaching-page__badge">{{ $formAssignments->count() }}</span>
                    </div>

                    @forelse ($formAssignments as $assignment)
                        <article class="teaching-page__form-card">
                            <div class="teaching-page__class">
                                <span class="teaching-page__icon">
                                    <x-filament::icon icon="heroicon-o-home-modern" class="h-5 w-5" />
                                </span>
                                <div>
                                    <strong>
                                        {{ $assignment->schoolClass?->name }}
                                        @if ($assignment->classSection)
                                            {{ $assignment->classSection->name }}
                                        @endif
                                    </strong>
                                    <span>{{ str($assignment->assignment_role)->replace('_', ' ')->title() }}</span>
                                </div>
                            </div>
                            <div class="teaching-page__detail">
                                {{ $assignment->academicYear?->name ?? 'Session not set' }}
                                @if ($assignment->term)
                                    · {{ $assignment->term->name }}
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="teaching-page__empty">No form class has been assigned yet.</div>
                    @endforelse
                </section>

                <section class="teaching-page__panel">
                    <div class="teaching-page__panel-head">
                        <div>
                            <h2>Subject Teaching</h2>
                            <p>Subjects you are responsible for entering scores against.</p>
                        </div>
                        <span class="teaching-page__badge">{{ $subjectAssignments->count() }}</span>
                    </div>

                    <div class="teaching-page__subjects">
                        @forelse ($subjectGroups as $subjectName => $assignments)
                            <article class="teaching-page__subject">
                                <div class="teaching-page__subject-top">
                                    <div class="teaching-page__subject-name">{{ $subjectName ?: 'Subject not set' }}</div>
                                    <span class="teaching-page__badge">{{ $assignments->count() }}</span>
                                </div>

                                <div class="teaching-page__chips">
                                    @foreach ($assignments as $assignment)
                                        <span class="teaching-page__chip">
                                            {{ $assignment->schoolClass?->name }}
                                            @if ($assignment->classSection)
                                                {{ $assignment->classSection->name }}
                                            @endif
                                            @if ($assignment->term)
                                                · {{ $assignment->term->name }}
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </article>
                        @empty
                            <div class="teaching-page__empty">No subject has been assigned to you yet.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    @endif
</x-filament-panels::page>
