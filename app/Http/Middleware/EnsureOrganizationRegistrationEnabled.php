<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationRegistrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('events.allow_organization_registration')) {
            abort(404);
        }

        return $next($request);
    }
}
