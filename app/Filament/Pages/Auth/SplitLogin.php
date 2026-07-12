<?php

namespace App\Filament\Pages\Auth;

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

        if (! $response || ! $this->shouldRedirectSuperAdminToAdminPanel()) {
            return $response;
        }

        $adminUrl = Filament::getPanel('admin')->getUrl();

        return new class($adminUrl) implements LoginResponse
        {
            public function __construct(private readonly string $adminUrl) {}

            public function toResponse($request)
            {
                session()->forget('url.intended');

                return redirect()->to($this->adminUrl);
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
