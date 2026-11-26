<?php

namespace Tests\Feature\Marketplace;

use App\Models\Attendee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_attendee_is_redirected_to_onboarding(): void
    {
        config(['services.telegram.channel_id' => '@demo_channel']);

        $attendee = Attendee::create([
            'telegram_id' => '12345',
            'name' => 'Abebe',
            'locale' => 'en',
        ]);

        $this->withSession(['public_attendee_id' => $attendee->id])
            ->get(route('public.discover'))
            ->assertRedirect(route('public.onboarding'));
    }

    public function test_register_completes_profile_when_channel_not_required(): void
    {
        config(['services.telegram.channel_id' => null]);

        $attendee = Attendee::create([
            'telegram_id' => '12345',
            'name' => 'Abebe',
            'locale' => 'en',
        ]);

        $this->withSession(['public_attendee_id' => $attendee->id])
            ->post(route('public.onboarding.register'), [
                'name' => 'Abebe Kebede',
                'buyer_phone_local' => '911223344',
                'email' => 'abebe@example.com',
                'notifications_enabled' => '1',
            ])
            ->assertRedirect(route('public.discover'));

        $attendee->refresh();
        $this->assertNotNull($attendee->profile_completed_at);
        $this->assertSame('+251911223344', $attendee->phone);
        $this->assertTrue($attendee->isOnboarded());
        $this->assertTrue($attendee->notifications_enabled);
    }

    public function test_channel_check_marks_membership(): void
    {
        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.channel_id' => '@demo_channel',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => ['status' => 'member'],
            ]),
        ]);

        $attendee = Attendee::create([
            'telegram_id' => '12345',
            'name' => 'Abebe',
            'locale' => 'en',
        ]);

        $this->withSession(['public_attendee_id' => $attendee->id])
            ->postJson(route('public.onboarding.channel'))
            ->assertOk()
            ->assertJson(['ok' => true, 'joined' => true]);

        $this->assertNotNull($attendee->fresh()->channel_joined_at);
    }

    public function test_webhook_start_sends_welcome(): void
    {
        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.webhook_secret' => null,
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        $this->postJson(route('webhooks.telegram'), [
            'message' => [
                'chat' => ['id' => 999],
                'text' => '/start',
            ],
        ])->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && ($request['chat_id'] ?? null) == 999;
        });
    }
}
