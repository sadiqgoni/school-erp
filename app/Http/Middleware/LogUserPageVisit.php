<?php

namespace App\Http\Middleware;

use App\Support\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogUserPageVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user() && $request->isMethod('GET') && $response->isSuccessful()) {
            AuditLogger::log(
                action: 'page_view',
                description: 'Visited '.$request->path(),
                user: $request->user(),
                request: $request,
            );
        }

        return $response;
    }
}
