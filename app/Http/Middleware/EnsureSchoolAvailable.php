<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolAvailable
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $school = Filament::getTenant();

        if (! $user instanceof User || ! $school instanceof School || $user->isSuperAdmin()) {
            return $next($request);
        }

        abort_unless($school->is_active, 403, 'This school portal has been deactivated. Contact the platform administrator.');
        abort_if($school->isSubscriptionExpired(), 403, 'This school subscription has expired. Contact the platform administrator.');

        return $next($request);
    }
}
