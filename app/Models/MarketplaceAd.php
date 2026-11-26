<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MarketplaceAd extends Model
{
    public const PLACEMENT_CAROUSEL = 'discover_carousel';

    public const PLACEMENT_INLINE = 'discover_inline';

    /** @deprecated Kept for older code/tests during transition */
    public const PLACEMENT_DISCOVER = self::PLACEMENT_CAROUSEL;

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
            self::PLACEMENT_CAROUSEL => 'Discover — top carousel',
            self::PLACEMENT_INLINE => 'Discover — between events',
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
                // Inclusive: stay visible through the scheduled end timestamp.
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function imageUrl(): ?string
    {
        if (! filled($this->image_path)) {
            return null;
        }

        return asset('storage/'.$this->image_path);
    }

    public function placementLabel(): string
    {
        return self::placements()[$this->placement] ?? $this->placement;
    }

    public static function forPlacement(string $placement)
    {
        return self::query()
            ->currentlyActive()
            ->where('placement', $placement)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
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
