<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Models\User;
use App\Support\CurrentDivision;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * With one Filament tenant per client school (not per division), a resolved
 * tenant no longer implies which division is active. This gate runs right
 * after tenant identification and makes sure a division is selected before
 * any division-scoped query executes: auto-picks it when there's only one
 * choice, sends the user to the picker when there's more than one, and
 * denies access outright when there's none.
 *
 * Registered isPersistent: true, same as EnsureSchoolAvailable — Filament's
 * IdentifyTenant middleware skips non-persistent tenant middleware on
 * Livewire/AJAX requests, and a division switch happens over exactly that
 * kind of request.
 */
class EnsureDivisionSelected
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = Filament::getTenant();

        if (! $user instanceof User || ! $tenant instanceof School) {
            return $next($request);
        }

        if (CurrentDivision::get()) {
            return $next($request);
        }

        $available = CurrentDivision::availableFor($user, $tenant);

        if ($available->count() === 1) {
            CurrentDivision::set($available->first(), $tenant);

            return $next($request);
        }

        if ($available->isEmpty()) {
            abort(403, 'You do not have access to any section of this school.');
        }

        if ($request->routeIs('filament.school.pages.select-division')) {
            return $next($request);
        }

        return redirect()->route('filament.school.pages.select-division');
    }
}
