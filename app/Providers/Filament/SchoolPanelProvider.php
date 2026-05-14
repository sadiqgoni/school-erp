<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\SchoolDashboardSummary;
use App\Filament\Widgets\SchoolWelcomeHero;
use App\Http\Middleware\EnsureActiveUser;
use App\Models\School;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
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
            ->brandName('School Dice')
            ->brandLogo(asset('images/branding/school-dice-logo-ful.png'))
            ->brandLogoHeight('75px')
            ->login()
            ->spa()
            ->colors([
                'primary' => Color::Teal,
                'gray' => Color::Slate,
            ])
            ->tenant(School::class, slugAttribute: 'slug')
            ->tenantMenu()
            ->sidebarWidth('280px')
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/panel-theme.css')
            ->navigationGroups([
                'Teacher Portal',
                'School Setup',
                'Students',
                'Staff',
                'Finance Setup',
                'Billing & Payments',
                'Accounts',
                'Exams & Reports',
                'System',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
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
            ]);
    }
}
