<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login;
use Illuminate\Contracts\Support\Htmlable;

class SplitLogin extends Login
{
    protected static string $layout = 'filament.layouts.auth';

    protected string $view = 'filament.pages.auth.split-login';

    protected string|\Filament\Support\Enums\Width|null $maxWidth = 'w-full';

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
}
