<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Event;
use Illuminate\Http\Request;

class DiscoverController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $events = Event::query()
            ->with(['company'])
            ->withCount('tickets')
            ->publiclyListed()
            ->upcoming()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$q}%"));
                });
            })
            ->orderBy('start_at')
            ->simplePaginate(12)
            ->withQueryString();

        return view('public.discover.index', compact('events', 'q'));
    }

    public function organizers(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        // Premium (subscribed) organizations only — show even if they have no upcoming events yet.
        $organizers = Company::query()
            ->publicMarketplace()
            ->withCount([
                'events as active_events_count' => fn ($query) => $query->where('status', 'active'),
            ])
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->simplePaginate(12)
            ->withQueryString();

        return view('public.discover.organizers', compact('organizers', 'q'));
    }

    public function organizer(Company $company)
    {
        if (! $company->isPremium() || $company->isSuspended()) {
            abort(404);
        }

        $events = Event::query()
            ->withCount('tickets')
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->orderByDesc('start_at')
            ->get();

        return view('public.discover.organizer', compact('company', 'events'));
    }

    public function event(Event $event)
    {
        $event->load(['company.bankAccounts' => fn ($q) => $q->where('is_active', true)])
            ->loadCount('tickets');

        if (! $event->company || ! $event->company->isPremium() || $event->company->isSuspended() || $event->status !== 'active') {
            abort(404);
        }

        return view('public.discover.event', compact('event'));
    }
}
