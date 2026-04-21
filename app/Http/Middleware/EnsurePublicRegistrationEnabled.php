<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePublicRegistrationEnabled
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('events.allow_public_registration', false)) {
            return redirect()->route('login')
                ->with('error', 'Registration is not open. Ask your organization admin for an account.');
        }

        return $next($request);
    }
}
