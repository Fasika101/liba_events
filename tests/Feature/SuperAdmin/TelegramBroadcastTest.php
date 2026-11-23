<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Attendee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_broadcast_to_users_and_channel(): void
    {
        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.channel_id' => '@demo_channel',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);

        Attendee::create([
            'telegram_id' => '111',
            'name' => 'Abebe',
            'locale' => 'en',
            'notifications_enabled' => true,
            'profile_completed_at' => now(),
            'channel_joined_at' => now(),
        ]);

        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('super-admin.telegram-broadcast.send'), [
                'message_en' => 'New update for everyone',
                'message_am' => 'አዲስ ዝመና',
                'to_users' => '1',
                'to_channel' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && ($request['chat_id'] ?? null) == '111';
        });

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage')
                && ($request['chat_id'] ?? null) === '@demo_channel';
        });
    }
}
