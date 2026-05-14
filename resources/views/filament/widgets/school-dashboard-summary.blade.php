<x-filament-widgets::widget>
    @once
        <style>
            .school-dashboard-summary{display:grid;gap:2rem}.school-dashboard-summary__topline{display:flex;align-items:center;justify-content:space-between;gap:1rem;border-bottom:1px solid rgba(15,118,110,.1);padding-bottom:1rem}.school-dashboard-summary__school{color:#334155;font-size:.98rem;font-weight:800}.dark .school-dashboard-summary__school{color:#f8fafc}.school-dashboard-summary__meta{display:flex;flex-wrap:wrap;gap:.35rem;color:#475569;font-size:.92rem;font-weight:600}.dark .school-dashboard-summary__meta{color:#cbd5e1}.school-dashboard-summary__section{display:grid;gap:1rem}.school-dashboard-summary__section header h2{color:#0f172a;font-size:1.18rem;font-weight:800;line-height:1.2}.dark .school-dashboard-summary__section header h2{color:#f8fafc}.school-dashboard-summary__section header p{margin-top:.25rem;color:#64748b;font-size:.96rem}.dark .school-dashboard-summary__section header p{color:#94a3b8}.school-dashboard-summary__grid{display:grid;grid-template-columns:repeat(1,minmax(0,1fr));gap:1rem}@media (min-width:768px){.school-dashboard-summary__grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media (min-width:1280px){.school-dashboard-summary__grid--students{grid-template-columns:repeat(5,minmax(0,1fr))}}.school-dashboard-summary__card{display:grid;min-height:10rem;gap:.7rem;align-content:center;border-radius:.55rem;border:1px solid rgba(15,118,110,.12);background:rgba(255,255,255,.82);padding:1.35rem;box-shadow:0 8px 22px rgba(15,23,42,.06)}.dark .school-dashboard-summary__card{background:rgba(15,23,42,.45);border-color:rgba(148,163,184,.16)}.school-dashboard-summary__label{color:#5f6673;font-size:.96rem;font-weight:700}.dark .school-dashboard-summary__label{color:#cbd5e1}.school-dashboard-summary__card strong{color:#020617;font-size:clamp(2rem,5vw,2.55rem);font-weight:850;line-height:1}.dark .school-dashboard-summary__card strong{color:#f8fafc}.school-dashboard-summary__description{display:flex;align-items:center;gap:.35rem;color:rgb(37 99 235);font-size:.93rem;font-weight:700;line-height:1.35}.school-dashboard-summary__description svg{flex:0 0 auto}
        </style>
    @endonce

    <div class="school-dashboard-summary">
        <div class="school-dashboard-summary__topline">
            <div>
                <div class="school-dashboard-summary__school">{{ $schoolName }}</div>
                <div class="school-dashboard-summary__meta">
                    @if ($divisionName)
                        <span>{{ $divisionName }}</span>
                        <span aria-hidden="true">|</span>
                    @endif
                    <span>Session: {{ $sessionName }}</span>
                    <span aria-hidden="true">|</span>
                    <span>Term: {{ $termName }}</span>
                </div>
            </div>
        </div>

        <section class="school-dashboard-summary__section">
            <header>
                <h2>Student Statistics</h2>
                <p>A summary of student data</p>
            </header>

            <div class="school-dashboard-summary__grid school-dashboard-summary__grid--students">
                @foreach ($studentCards as $card)
                    <article class="school-dashboard-summary__card">
                        <span class="school-dashboard-summary__label">{{ $card['label'] }}</span>
                        <strong>{{ $card['value'] }}</strong>
                        <span class="school-dashboard-summary__description">
                            <x-filament::icon :icon="$card['icon']" class="h-5 w-5" />
                            {{ $card['description'] }}
                        </span>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="school-dashboard-summary__section">
            <header>
                <h2>Academic Calendar</h2>
                <p>Key academic dates and school activities.</p>
            </header>

            <div class="school-dashboard-summary__grid">
                @foreach ($calendarCards as $card)
                    <article class="school-dashboard-summary__card">
                        <span class="school-dashboard-summary__label">{{ $card['label'] }}</span>
                        <strong>{{ $card['value'] }}</strong>
                        <span class="school-dashboard-summary__description">
                            <x-filament::icon :icon="$card['icon']" class="h-5 w-5" />
                            {{ $card['range'] }}
                        </span>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</x-filament-widgets::widget>
