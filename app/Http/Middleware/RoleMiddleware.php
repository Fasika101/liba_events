<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $allowedRoles = collect($roles)
            ->flatMap(fn ($role) => explode('|', $role))
            ->filter()
            ->all();

        if (! $user || (! empty($allowedRoles) && ! in_array($user->role, $allowedRoles, true))) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
