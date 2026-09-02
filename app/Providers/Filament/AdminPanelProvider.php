<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Pages\Auth\SplitLogin;
use App\Filament\Resources\CommunicationLogs\CommunicationLogResource;
use App\Filament\Resources\Schools\SchoolResource;
use App\Filament\Resources\UserActivities\UserActivityResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\PlatformOverview;
use App\Filament\Widgets\RecentPlatformActivity;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\RedirectSchoolUserFromAdmin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
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
            ->domain(config('app.central_domain'))
            ->login(SplitLogin::class)
            ->path('admin')
            ->brandName('School Dice Admin')
            ->brandLogo(asset('images/branding/school-dice-logo-ful.png'))
            ->brandLogoHeight('75px')
            ->registration(Register::class)
            ->passwordReset(RequestPasswordReset::class)
            ->spa()
            ->colors([
                'primary' => Color::Teal,
                'gray' => Color::Slate,
            ])
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldKeyBindingSuffix()
            ->sidebarWidth('280px')
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/panel-theme.css')
            ->navigationGroups([
                NavigationGroup::make('Platform')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->collapsible(false),
                NavigationGroup::make('People & Access')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->collapsible(false),
                NavigationGroup::make('Monitoring')
                    ->icon(Heroicon::OutlinedSignal)
                    ->collapsible(false),
                NavigationGroup::make('System')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->collapsed(),
            ])
            ->resources([
                SchoolResource::class,
                UserResource::class,
                UserActivityResource::class,
                CommunicationLogResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                PlatformOverview::class,
                RecentPlatformActivity::class,
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
                Authenticate::class,
                EnsureActiveUser::class,
                RedirectSchoolUserFromAdmin::class,
            ]);
    }
}
