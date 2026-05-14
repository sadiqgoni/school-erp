<x-filament-widgets::widget>
    @once
        <style>
            .school-dice-hero__masthead.is-centered{flex-direction:column;align-items:center;text-align:center}.school-dice-hero__masthead.is-centered .school-dice-hero__subtitle{margin-left:auto;margin-right:auto}.school-dice-hero__section-name{display:inline-flex;margin-top:.65rem;border-radius:999px;background:rgba(37,99,235,.1);padding:.2rem .7rem;color:rgb(37 99 235);font-size:.78rem;font-weight:800}
        </style>
    @endonce

    <x-filament::section>
        <div class="school-dice-hero">
            <div class="school-dice-hero__intro">
                <div class="school-dice-hero__masthead is-centered">
                    <div class="school-dice-hero__logo">
                        @if ($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $schoolName }} logo">
                        @else
                            <span>{{ str($schoolName)->substr(0, 1)->upper() }}</span>
                        @endif
                    </div>

                    <div>
                        <div class="school-dice-hero__eyebrow">School Dice Workspace</div>

                        <h2 class="school-dice-hero__title">
                            {{ $schoolName }}

                            @if ($schoolCode)
                                <span class="school-dice-hero__code">{{ $schoolCode }}</span>
                            @endif
                        </h2>

                        @if ($sectionName)
                            <div class="school-dice-hero__section-name">{{ $sectionName }}</div>
                        @endif

                        <p class="school-dice-hero__subtitle">
                            Manage admissions, staff, finance, attendance, exams, and reports from one school workspace.
                        </p>
                    </div>
                </div>

                <div class="school-dice-hero__progress">
                    <div class="school-dice-hero__progress-meta">
                        <span>Setup progress</span>
                        <span>{{ $progress }}%</span>
                    </div>

                    <div class="school-dice-hero__progress-bar">
                        <span style="width: {{ $progress }}%"></span>
                    </div>
                </div>
            </div>

            <div class="school-dice-hero__content">
                <div class="school-dice-hero__actions">
                    @foreach ($actions as $action)
                        <a href="{{ $action['url'] }}" class="school-dice-hero__action">
                            <span class="school-dice-hero__action-icon">
                                <x-filament::icon :icon="$action['icon']" class="h-5 w-5" />
                            </span>

                            <span class="school-dice-hero__action-copy">
                                <span>{{ $action['label'] }}</span>
                                <small>{{ $action['description'] }}</small>
                            </span>
                        </a>
                    @endforeach
                </div>

                <div class="school-dice-hero__checklist">
                    <div class="school-dice-hero__checklist-heading">Workspace checklist</div>

                    <div class="school-dice-hero__checklist-list">
                        @foreach ($setupChecks as $check)
                            <div class="school-dice-hero__check">
                                <span @class([
                                    'school-dice-hero__check-icon',
                                    'is-done' => $check['done'],
                                    'is-pending' => ! $check['done'],
                                ])>
                                    <x-filament::icon :icon="$check['done'] ? 'heroicon-o-check' : 'heroicon-o-clock'" class="h-4 w-4" />
                                </span>

                                <span class="school-dice-hero__check-copy">
                                    <span>{{ $check['label'] }}</span>
                                    <small>{{ $check['description'] }}</small>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
