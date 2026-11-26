<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Attendee;
use App\Models\MarketplaceAd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceAdTest extends TestCase
{
    use RefreshDatabase;

    private function asTelegramUser()
    {
        $attendee = Attendee::create([
            'telegram_id' => (string) random_int(100000, 999999999),
            'name' => 'Telegram User',
            'locale' => 'en',
            'channel_joined_at' => now(),
            'profile_completed_at' => now(),
        ]);

        return $this->withSession(['public_attendee_id' => $attendee->id]);
    }

    public function test_super_admin_can_create_ad(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('super-admin.marketplace-ads.store'), [
                'title' => 'Quiet promo',
                'body' => 'A soft line under Discover',
                'link_url' => 'https://example.com',
                'placement' => MarketplaceAd::PLACEMENT_CAROUSEL,
                'sort_order' => 1,
                'is_active' => '1',
            ])
            ->assertRedirect(route('super-admin.marketplace-ads.index'));

        $this->assertDatabaseHas('marketplace_ads', [
            'title' => 'Quiet promo',
            'placement' => 'discover_carousel',
            'is_active' => 1,
        ]);
    }

    public function test_active_carousel_ad_appears_on_discover(): void
    {
        MarketplaceAd::create([
            'title' => 'Spring gathering sponsor',
            'body' => 'Subtle placement',
            'placement' => MarketplaceAd::PLACEMENT_CAROUSEL,
            'is_active' => true,
            'sort_order' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(3),
        ]);

        $this->asTelegramUser()
            ->get(route('public.discover'))
            ->assertOk()
            ->assertSee('Spring gathering sponsor')
            ->assertSee(__('public.sponsored'))
            ->assertSee('© '.now()->year)
            ->assertSee('data-ad-carousel', false);
    }

    public function test_scheduled_future_ad_is_hidden_until_start(): void
    {
        MarketplaceAd::create([
            'title' => 'Future promo',
            'placement' => MarketplaceAd::PLACEMENT_CAROUSEL,
            'is_active' => true,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(5),
        ]);

        $this->asTelegramUser()
            ->get(route('public.discover'))
            ->assertOk()
            ->assertDontSee('Future promo');
    }

    public function test_inactive_ad_is_hidden(): void
    {
        MarketplaceAd::create([
            'title' => 'Hidden promo',
            'placement' => MarketplaceAd::PLACEMENT_CAROUSEL,
            'is_active' => false,
        ]);

        $this->asTelegramUser()
            ->get(route('public.discover'))
            ->assertOk()
            ->assertDontSee('Hidden promo');
    }
}
