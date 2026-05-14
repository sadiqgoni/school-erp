<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Schools\SchoolResource;
use App\Filament\Resources\Users\UserResource;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\RedirectSchoolUserFromAdmin;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->login()
            ->registration()
            ->path('admin')
            ->brandName('School Dice Admin')
            ->brandLogo(asset('images/branding/school-dice-logo-ful.png'))
            ->brandLogoHeight('75px')
            ->spa()
            ->colors([
                'primary' => Color::Teal,
                'gray' => Color::Slate,
            ])
            ->sidebarWidth('280px')
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/panel-theme.css')
            ->navigationGroups([
                'System',
            ])
            ->resources([
                SchoolResource::class,
                UserResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
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
                RedirectSchoolUserFromAdmin::class,
                Authenticate::class,
                EnsureActiveUser::class,
            ]);
    }
}
