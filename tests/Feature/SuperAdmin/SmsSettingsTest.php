<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Setting;
use App\Models\User;
use App\Services\Sms\SmsethiopiaClient;
use App\Services\SmsVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_sms_settings_page(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('super-admin.sms-settings.edit'))
            ->assertOk()
            ->assertSee('SMS settings')
            ->assertSee('SMSEthiopia');
    }

    public function test_super_admin_can_save_sms_settings(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->put(route('super-admin.sms-settings.update'), [
                'driver' => 'smsethiopia',
                'api_key' => 'test-api-key-123',
                'sender_id' => 'LIBAEVENTS',
                'message_template' => 'Code: {code}',
                'otp_length' => 6,
                'otp_expiry_minutes' => 10,
            ])
            ->assertRedirect(route('super-admin.sms-settings.edit'));

        $this->assertSame('smsethiopia', Setting::get('sms.driver'));
        $this->assertSame('test-api-key-123', Setting::get('sms.api_key'));
        $this->assertSame('LIBAEVENTS', Setting::get('sms.sender_id'));
    }

    public function test_smsethiopia_client_sends_sms_with_correct_payload(): void
    {
        Http::fake([
            'smsethiopia.com/api/sms/send' => Http::response([
                'sent' => true,
                'id' => 0,
                'description' => 'Accepted for delivery',
            ], 200),
        ]);

        Setting::set('sms.api_key', 'live-key');

        $client = app(SmsethiopiaClient::class);
        $result = $client->send('+251912345678', 'Your OTP is 123456');

        $this->assertTrue($result['sent']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://smsethiopia.com/api/sms/send'
                && $request->hasHeader('KEY', 'live-key')
                && $request['msisdn'] === '251912345678'
                && $request['text'] === 'Your OTP is 123456';
        });
    }

    public function test_sms_verification_service_uses_smsethiopia_when_configured(): void
    {
        Http::fake([
            'smsethiopia.com/api/sms/send' => Http::response(['sent' => true, 'description' => 'Accepted for delivery'], 200),
        ]);

        Setting::set('sms.driver', 'smsethiopia');
        Setting::set('sms.api_key', 'live-key');
        Setting::set('sms.message_template', 'Code {code}');

        $code = app(SmsVerificationService::class)->sendOtp('+251911222333');

        $this->assertSame(6, strlen($code));
        Http::assertSentCount(1);
    }

    public function test_super_admin_can_send_test_sms(): void
    {
        Http::fake([
            'smsethiopia.com/api/sms/send' => Http::response(['sent' => true, 'description' => 'Accepted for delivery'], 200),
        ]);

        Setting::set('sms.driver', 'smsethiopia');
        Setting::set('sms.api_key', 'live-key');

        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->post(route('super-admin.sms-settings.test'), [
                'test_phone' => '+251912345678',
            ])
            ->assertRedirect(route('super-admin.sms-settings.edit'))
            ->assertSessionHas('status');

        Http::assertSentCount(1);
    }
}
