<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Sms\SmsethiopiaClient;
use App\Services\SmsVerificationService;
use App\Support\SmsConfig;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SmsSettingsController extends Controller
{
    public function edit(SmsethiopiaClient $client)
    {
        $driver = SmsConfig::driver();
        $keyReadable = filled(SmsConfig::apiKey());
        $keyCorrupt = Setting::isUnreadable('sms.api_key');

        return view('super-admin.sms-settings.edit', [
            'settings' => [
                'driver' => $driver,
                'sender_id' => SmsConfig::senderId(),
                'message_template' => SmsConfig::messageTemplate(),
                'otp_length' => SmsConfig::otpLength(),
                'otp_expiry_minutes' => SmsConfig::otpExpiryMinutes(),
                'has_api_key' => $keyReadable,
                'api_key_masked' => Setting::masked('sms.api_key'),
                'api_key_corrupt' => $keyCorrupt,
                'sender_id_price_etb' => SmsConfig::senderIdPriceEtb(),
                'verify_ssl' => (bool) config('events.sms.verify_ssl', true),
                'is_live_driver' => $driver === 'smsethiopia',
                'is_configured' => SmsConfig::isConfigured(),
            ],
            'probe' => $client->probe(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'driver' => ['required', Rule::in(['log', 'smsethiopia'])],
            'api_key' => ['nullable', 'string', 'max:500'],
            'sender_id' => ['nullable', 'string', 'max:50'],
            'message_template' => ['required', 'string', 'max:500'],
            'otp_length' => ['required', 'integer', 'min:4', 'max:8'],
            'otp_expiry_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'sender_id_price_etb' => ['nullable', 'numeric', 'min:0'],
        ]);

        $needsFreshKey = $data['driver'] === 'smsethiopia'
            && ! filled($data['api_key'])
            && (! filled(SmsConfig::apiKey()) || Setting::isUnreadable('sms.api_key'));

        if ($needsFreshKey) {
            return back()
                ->withErrors(['api_key' => Setting::isUnreadable('sms.api_key')
                    ? 'Saved API key cannot be decrypted. Paste the key again (APP_KEY may have changed on live).'
                    : 'API key is required when using SMSEthiopia.'])
                ->withInput();
        }

        Setting::set('sms.driver', $data['driver']);
        Setting::set('sms.sender_id', $data['sender_id'] ?? '');
        Setting::set('sms.message_template', $data['message_template']);
        Setting::set('sms.otp_length', (string) $data['otp_length']);
        Setting::set('sms.otp_expiry_minutes', (string) $data['otp_expiry_minutes']);
        Setting::set('sms.sender_id_price_etb', (string) ($data['sender_id_price_etb'] ?? ''));

        // Never wipe an existing key when the password field is left blank.
        if (filled($data['api_key'])) {
            Setting::set('sms.api_key', $data['api_key']);
        }

        return redirect()->route('super-admin.sms-settings.edit')
            ->with('status', filled(SmsConfig::apiKey())
                ? 'SMS settings saved. Your API key stays on file until you replace it.'
                : 'SMS settings saved successfully.');
    }

    public function test(Request $request, SmsVerificationService $sms)
    {
        $data = $request->validate([
            'test_phone' => ['required', 'regex:/^\+251[0-9]{9}$/'],
            'test_message' => ['nullable', 'string', 'max:500'],
        ]);

        if (Setting::isUnreadable('sms.api_key')) {
            return redirect()->route('super-admin.sms-settings.edit')
                ->with('error', 'Saved API key cannot be decrypted (APP_KEY likely changed). Paste the API key again and Save settings, then retry the test.');
        }

        if (! SmsConfig::isConfigured()) {
            return redirect()->route('super-admin.sms-settings.edit')
                ->with('error', 'Configure and save your SMS provider settings before sending a test message. Provider must be SMSEthiopia with a valid API key.');
        }

        if (SmsConfig::driver() === 'log') {
            return redirect()->route('super-admin.sms-settings.edit')
                ->with('error', 'Provider is still set to “Log only”. Switch to SMSEthiopia, save, then send the test — otherwise no real SMS is sent.');
        }

        try {
            $sms->sendTestMessage($data['test_phone'], $data['test_message'] ?? null);
        } catch (\Throwable $exception) {
            return redirect()->route('super-admin.sms-settings.edit')
                ->with('error', 'Test SMS failed: '.$exception->getMessage())
                ->withInput();
        }

        return redirect()->route('super-admin.sms-settings.edit')
            ->with('status', "Live test SMS accepted by SMSEthiopia for {$data['test_phone']}.");
    }
}
