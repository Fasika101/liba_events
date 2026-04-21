<?php

namespace App\Providers;

use App\Helpers\EthiopianCalendar;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // For older MySQL versions with limited index length (e.g., < 5.7)
        Schema::defaultStringLength(191);

        // @ethdate($carbonDate) → "1 Meskerem 2012 E.C."
        Blade::directive('ethdate', function (string $expression) {
            return "<?php echo \App\Helpers\EthiopianCalendar::format($expression); ?>";
        });

        Route::bind('event', function (string $value) {
            $user = auth()->user();
            $event = Event::whereKey($value)->firstOrFail();

            if (! $user) {
                return $event;
            }

            if ($user->isSuperAdmin()) {
                return $event;
            }

            $companyId = $user->company_id;
            if ($companyId !== null && (int) $event->company_id === (int) $companyId) {
                return $event;
            }

            abort(404);
        });

        Route::bind('ticket', function (string $value) {
            $ticket = Ticket::with('event')->whereKey($value)->firstOrFail();
            $user = auth()->user();

            if (! $user) {
                return $ticket;
            }

            if ($user->isSuperAdmin()) {
                return $ticket;
            }

            if ($user->isAgent() && (int) $ticket->agent_id === (int) $user->id) {
                return $ticket;
            }

            if ($user->isAdmin() && $user->company_id !== null
                && (int) $ticket->event->company_id === (int) $user->company_id) {
                return $ticket;
            }

            abort($user->isAgent() ? 403 : 404);
        });
    }
}
