<?php

namespace App\Filament\Pages\Auth;

use App\Models\School;
use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Contracts\Support\Htmlable;

class SplitLogin extends Login
{
    protected static string $layout = 'filament.layouts.auth';

    protected string $view = 'filament.pages.auth.split-login';

    protected string|\Filament\Support\Enums\Width|null $maxWidth = 'w-full';

    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        if (! $response) {
            return $response;
        }

        if ($this->shouldRedirectSuperAdminToAdminPanel()) {
            return $this->redirectResponseTo(Filament::getPanel('admin')->getUrl());
        }

        // Filament's default post-login redirect ignores which subdomain the
        // request actually came in on and uses the user's "default" tenant
        // instead (sorted by the school_user pivot's is_primary flag) - so a
        // user attached to more than one division of the same school (every
        // school admin, by design) could log in on one division's subdomain
        // and land on a different one. Keep them on the school they actually
        // logged into whenever they have access to it.
        if ($school = $this->schoolForCurrentDomain()) {
            $user = Filament::auth()->user();

            if ($user instanceof User && $user->canAccessTenant($school)) {
                return $this->redirectResponseTo($school->portalUrl());
            }
        }

        return $response;
    }

    protected function schoolForCurrentDomain(): ?School
    {
        $host = request()?->getHost();
        $centralDomain = config('app.central_domain');

        if (blank($host) || blank($centralDomain) || ! str_ends_with($host, '.'.$centralDomain)) {
            return null;
        }

        $slug = substr($host, 0, -(strlen($centralDomain) + 1));

        return School::query()
            ->withoutGlobalScope('school-panel-current-tenant')
            ->where('slug', $slug)
            ->first();
    }

    protected function redirectResponseTo(string $url): LoginResponse
    {
        return new class($url) implements LoginResponse
        {
            public function __construct(private readonly string $url) {}

            public function toResponse($request)
            {
                session()->forget('url.intended');

                return redirect()->to($this->url);
            }
        };
    }

    public function hasLogo(): bool
    {
        return false;
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    protected function shouldRedirectSuperAdminToAdminPanel(): bool
    {
        $user = Filament::auth()->user();

        return Filament::getCurrentPanel()?->getId() === 'school'
            && $user instanceof User
            && $user->isSuperAdmin();
    }
}
