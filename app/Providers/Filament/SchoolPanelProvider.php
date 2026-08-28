<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Pages\Auth\SplitLogin;
use App\Filament\Widgets\FinanceSnapshot;
use App\Filament\Widgets\ParentDashboardSummary;
use App\Filament\Widgets\SchoolDashboardSummary;
use App\Filament\Widgets\SchoolWelcomeHero;
use App\Filament\Widgets\TeacherDashboard;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureSchoolAvailable;
use App\Models\School;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SchoolPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('school')
            ->path('portal')
            ->tenantDomain('{tenant}.'.config('app.central_domain'))
            ->brandName('School Dice')
            ->brandLogo(asset('images/branding/school-dice-logo-ful.png'))
            ->brandLogoHeight('75px')
            ->login(SplitLogin::class)
            ->passwordReset(RequestPasswordReset::class)
            ->spa()
            ->colors([
                'primary' => Color::Teal,
                'gray' => Color::Slate,
            ])
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldKeyBindingSuffix()
            ->tenant(School::class, slugAttribute: 'slug')
            ->tenantMenu()
            ->sidebarWidth('280px')
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(
                PanelsRenderHook::TOPBAR_LOGO_AFTER,
                fn (): View => view('filament.partials.portal-role-badge'),
            )
            ->viteTheme('resources/css/filament/panel-theme.css')
            ->navigationGroups([
                'Parent Portal',
                'Academics & Fees',
                'Teacher Workspace',
                'Class Management',
                'Classroom',
                'Communication',
                'School Setup',
                'Students',
                'Staff',
                'Payroll',
                'Finance Setup',
                'Billing & Payments',
                'Accounts',
                'Exams & Reports',
                'Safety & Transport',
                'System',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                TeacherDashboard::class,
                ParentDashboardSummary::class,
                FinanceSnapshot::class,
                SchoolWelcomeHero::class,
                SchoolDashboardSummary::class,
                // AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureActiveUser::class,
            ])
            // Filament resolves Filament::getTenant() inside its own IdentifyTenant
            // middleware, which runs as part of the tenant-middleware group — after
            // authMiddleware. Tenant-dependent checks must live here, not above.
            ->tenantMiddleware([
                EnsureSchoolAvailable::class,
            ], isPersistent: true);
    }
}
