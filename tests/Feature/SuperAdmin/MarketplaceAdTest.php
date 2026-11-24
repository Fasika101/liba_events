<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\MarketplaceAd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceAdTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_ad(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('super-admin.marketplace-ads.store'), [
                'title' => 'Quiet promo',
                'body' => 'A soft line under Discover',
                'link_url' => 'https://example.com',
                'placement' => MarketplaceAd::PLACEMENT_DISCOVER,
                'sort_order' => 1,
                'is_active' => '1',
            ])
            ->assertRedirect(route('super-admin.marketplace-ads.index'));

        $this->assertDatabaseHas('marketplace_ads', [
            'title' => 'Quiet promo',
            'placement' => 'discover',
            'is_active' => 1,
        ]);
    }

    public function test_active_discover_ad_appears_on_public_page(): void
    {
        MarketplaceAd::create([
            'title' => 'Spring gathering sponsor',
            'body' => 'Subtle placement',
            'placement' => MarketplaceAd::PLACEMENT_DISCOVER,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->get(route('public.discover'))
            ->assertOk()
            ->assertSee('Spring gathering sponsor')
            ->assertSee(__('public.sponsored'))
            ->assertSee('© '.now()->year);
    }

    public function test_inactive_ad_is_hidden(): void
    {
        MarketplaceAd::create([
            'title' => 'Hidden promo',
            'placement' => MarketplaceAd::PLACEMENT_DISCOVER,
            'is_active' => false,
        ]);

        $this->get(route('public.discover'))
            ->assertOk()
            ->assertDontSee('Hidden promo');
    }
}
