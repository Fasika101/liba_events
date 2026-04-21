<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyNotSuspended
{
    /**
     * Log out tenant users (admin/agent) when their organization is suspended.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($user->company_id === null) {
            return $next($request);
        }

        $user->loadMissing('company');

        if ($user->company && $user->company->is_suspended) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your account has been suspended. Please talk to the super admin.');
        }

        return $next($request);
    }
}
