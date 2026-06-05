@php
    $panelId = filament()->getCurrentPanel()?->getId();
    $isAdminPanel = $panelId === 'admin';
    $supportText = $isAdminPanel
        ? 'Platform owners can sign in to manage schools, sections, and system users.'
        : 'School owners, staff, and parents can sign in with the account created by the school.';
@endphp

<div
    class="school-auth-shell"
    x-data="{
        active: 'school',
        copy: {
            school: 'School owners can sign in to manage admissions, billing, classes, and reports.',
            staff: 'Teachers and staff can sign in to handle class work, scores, attendance, and daily operations.',
            parent: 'Parents can sign in to view children, invoices, payments, and published results.',
        },
        labels: {
            school: 'School',
            staff: 'Staff',
            parent: 'Parent',
        },
        focusEmail() {
            this.$nextTick(() => document.querySelector('.school-auth-form input[type=email]')?.focus())
        },
    }"
    x-init="focusEmail()"
>
    <aside class="school-auth-visual" aria-hidden="true">
            <div class="school-auth-grid"></div>
            <div class="school-auth-dot school-auth-dot-a"></div>
            <div class="school-auth-dot school-auth-dot-b"></div>
            <div class="school-auth-dot school-auth-dot-c"></div>

            <div class="school-auth-card school-auth-card-main">
                <div class="school-auth-card-head">
                    <div class="school-auth-card-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <div>
                        <p>Dashboard</p>
                        <span>School overview</span>
                    </div>
                </div>

                <div class="school-auth-bars">
                    <span style="height: 42%"></span>
                    <span style="height: 64%"></span>
                    <span style="height: 32%"></span>
                    <span style="height: 52%"></span>
                    <span style="height: 72%"></span>
                    <span style="height: 38%"></span>
                    <span style="height: 56%"></span>
                </div>

                <div class="school-auth-card-foot">
                    <span>Weekly attendance</span>
                    <strong>+12.5%</strong>
                </div>
            </div>

            <div class="school-auth-card school-auth-card-students">
                <div class="school-auth-student-dot"></div>
                <div>
                    <strong>1,247</strong>
                    <span>Students</span>
                </div>
                <div class="school-auth-progress">
                    <span></span>
                </div>
            </div>

            <div class="school-auth-card school-auth-card-result">
                <span>Results</span>
                <strong>A+</strong>
                <p>Top performer</p>
            </div>

            <div class="school-auth-copy">
                <h1>One workspace for every school day.</h1>
                <p>Admissions, classes, attendance, fees, communication, invoices, and report cards stay connected.</p>
            </div>
    </aside>

    <main class="school-auth-panel">
            <div class="school-auth-form-wrap">
                <div class="school-auth-brand">
                    <img src="{{ asset('images/branding/school-dice-logo-ful.png') }}" alt="School Dice logo">
                </div>

                <div class="school-auth-heading">
                    <h2>Welcome back</h2>
                    <p x-text="copy[active]">{{ $supportText }}</p>
                </div>

                <div class="school-auth-audience" role="tablist" aria-label="Account type">
                    <template x-for="(label, key) in labels" :key="key">
                        <button
                            type="button"
                            role="tab"
                            :aria-selected="active === key"
                            :class="{ 'is-active': active === key }"
                            x-text="label"
                            x-on:click="active = key; focusEmail()"
                        ></button>
                    </template>
                </div>

                <div class="school-auth-form">
                    {{ $this->content }}
                </div>

                <div class="school-auth-help">
                    @if ($isAdminPanel)
                        <p>New school? <a href="{{ filament()->getRegistrationUrl() }}">Create platform access</a></p>
                    @else
                        <p>Need an account? Contact your school administrator.</p>
                    @endif
                </div>
            </div>
        </main>
</div>
