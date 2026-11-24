<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MarketplaceAd extends Model
{
    public const PLACEMENT_DISCOVER = 'discover';

    public const PLACEMENT_ORGANIZERS = 'organizers';

    public const PLACEMENT_FOOTER = 'footer';

    protected $fillable = [
        'title',
        'body',
        'image_path',
        'link_url',
        'placement',
        'is_active',
        'sort_order',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public static function placements(): array
    {
        return [
            self::PLACEMENT_DISCOVER => 'Discover page',
            self::PLACEMENT_ORGANIZERS => 'Organizers page',
            self::PLACEMENT_FOOTER => 'Footer (all pages)',
        ];
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function imageUrl(): ?string
    {
        if (! filled($this->image_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }

    public function placementLabel(): string
    {
        return self::placements()[$this->placement] ?? $this->placement;
    }

    /**
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, self>>
     */
    public static function activeGrouped()
    {
        return once(function () {
            try {
                return self::query()
                    ->currentlyActive()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                    ->groupBy('placement');
            } catch (\Throwable) {
                return collect();
            }
        });
    }
}
