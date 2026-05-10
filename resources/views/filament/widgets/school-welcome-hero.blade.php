<x-filament-widgets::widget>
    <x-filament::section>
        <div class="school-dice-hero">
            <div class="school-dice-hero__intro">
                <div class="school-dice-hero__masthead">
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
                            Welcome, {{ $schoolName }}

                            @if ($schoolCode)
                                <span class="school-dice-hero__code">{{ $schoolCode }}</span>
                            @endif
                        </h2>

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
