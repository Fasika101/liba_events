<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MarketplaceAdController extends Controller
{
    public function index()
    {
        $ads = MarketplaceAd::query()
            ->orderBy('placement')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('super-admin.marketplace-ads.index', compact('ads'));
    }

    public function create()
    {
        return view('super-admin.marketplace-ads.create', [
            'ad' => new MarketplaceAd([
                'placement' => MarketplaceAd::PLACEMENT_DISCOVER,
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'placements' => MarketplaceAd::placements(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('marketplace-ads', 'public');
        }

        MarketplaceAd::create($data);

        return redirect()->route('super-admin.marketplace-ads.index')
            ->with('status', 'Ad created.');
    }

    public function edit(MarketplaceAd $marketplaceAd)
    {
        return view('super-admin.marketplace-ads.edit', [
            'ad' => $marketplaceAd,
            'placements' => MarketplaceAd::placements(),
        ]);
    }

    public function update(Request $request, MarketplaceAd $marketplaceAd)
    {
        $data = $this->validated($request);

        if ($request->boolean('remove_image') && $marketplaceAd->image_path) {
            Storage::disk('public')->delete($marketplaceAd->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($marketplaceAd->image_path) {
                Storage::disk('public')->delete($marketplaceAd->image_path);
            }
            $data['image_path'] = $request->file('image')->store('marketplace-ads', 'public');
        }

        $marketplaceAd->update($data);

        return redirect()->route('super-admin.marketplace-ads.index')
            ->with('status', 'Ad updated.');
    }

    public function destroy(MarketplaceAd $marketplaceAd)
    {
        if ($marketplaceAd->image_path) {
            Storage::disk('public')->delete($marketplaceAd->image_path);
        }

        $marketplaceAd->delete();

        return redirect()->route('super-admin.marketplace-ads.index')
            ->with('status', 'Ad deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:500'],
            'link_url' => ['nullable', 'url', 'max:500'],
            'placement' => ['required', Rule::in(array_keys(MarketplaceAd::placements()))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['body'] = $data['body'] ?? null;
        $data['link_url'] = $data['link_url'] ?? null;
        $data['starts_at'] = $data['starts_at'] ?? null;
        $data['ends_at'] = $data['ends_at'] ?? null;

        unset($data['image'], $data['remove_image']);

        return $data;
    }
}
