<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'                 => \App\Http\Middleware\RoleMiddleware::class,
            'registration.enabled' => \App\Http\Middleware\EnsurePublicRegistrationEnabled::class,
            'organization.registration.enabled' => \App\Http\Middleware\EnsureOrganizationRegistrationEnabled::class,
            'company.active'       => \App\Http\Middleware\EnsureCompanyNotSuspended::class,
            'public.locale'        => \App\Http\Middleware\SetPublicLocale::class,
            'public.onboarded'     => \App\Http\Middleware\EnsureMarketplaceOnboarded::class,
            'public.telegram'      => \App\Http\Middleware\EnsureTelegramMarketplaceUser::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/verify-et',
            'webhooks/telegram',
            'discover/telegram-auth',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
