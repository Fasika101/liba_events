<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companyId = auth()->user()->requireCompanyId();

        $events = Event::forCompany($companyId)
            ->withCount('tickets')
            ->withSum('tickets', 'price_paid')
            ->latest('start_at')
            ->paginate(10);

        return view('admin.events.index', compact('events'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.events.create', [
            'event' => new Event(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('events', 'public');
        }

        $data['company_id'] = auth()->user()->requireCompanyId();

        Event::create($data);

        return redirect()->route('admin.events.index')->with('status', 'Event created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        return redirect()->route('admin.events.edit', $event);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event)
    {
        return view('admin.events.edit', compact('event'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        $data = $this->validatedData($request, $event->id);

        if ($request->hasFile('photo')) {
            if ($event->photo_path) {
                Storage::disk('public')->delete($event->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('events', 'public');
        }

        $event->update($data);

        return redirect()->route('admin.events.index')->with('status', 'Event updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        $event->delete();

        return redirect()->route('admin.events.index')->with('status', 'Event archived. All ticket sales and buyer records have been preserved.');
    }

    protected function validatedData(Request $request, ?int $eventId = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'string', 'in:active,draft,archived'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $data['currency'] = Str::upper($data['currency']);

        return $data;
    }
}
