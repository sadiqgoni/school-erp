<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectSchoolUserFromAdmin
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user instanceof User
            && $user->is_active
            && (! $user->is_platform_admin)
            && $request->is('admin', 'admin/*')
            && ($school = $user->getDefaultTenant(Filament::getCurrentOrDefaultPanel()))
        ) {
            return redirect()->to(url("/portal/{$school->slug}"));
        }

        return $next($request);
    }
}
