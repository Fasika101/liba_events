<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SmsVerificationService;
use App\Support\SmsConfig;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SmsSettingsController extends Controller
{
    public function edit()
    {
        return view('super-admin.sms-settings.edit', [
            'settings' => [
                'driver' => SmsConfig::driver(),
                'sender_id' => SmsConfig::senderId(),
                'message_template' => SmsConfig::messageTemplate(),
                'otp_length' => SmsConfig::otpLength(),
                'otp_expiry_minutes' => SmsConfig::otpExpiryMinutes(),
                'has_api_key' => filled(SmsConfig::apiKey()),
                'sender_id_price_etb' => SmsConfig::senderIdPriceEtb(),
            ],
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

        if ($data['driver'] === 'smsethiopia' && ! filled($data['api_key']) && ! filled(SmsConfig::apiKey())) {
            return back()
                ->withErrors(['api_key' => 'API key is required when using SMSEthiopia.'])
                ->withInput();
        }

        Setting::set('sms.driver', $data['driver']);
        Setting::set('sms.sender_id', $data['sender_id'] ?? '');
        Setting::set('sms.message_template', $data['message_template']);
        Setting::set('sms.otp_length', (string) $data['otp_length']);
        Setting::set('sms.otp_expiry_minutes', (string) $data['otp_expiry_minutes']);
        Setting::set('sms.sender_id_price_etb', (string) ($data['sender_id_price_etb'] ?? ''));

        if (filled($data['api_key'])) {
            Setting::set('sms.api_key', $data['api_key']);
        }

        return redirect()->route('super-admin.sms-settings.edit')
            ->with('status', 'SMS settings saved successfully.');
    }

    public function test(Request $request, SmsVerificationService $sms)
    {
        $data = $request->validate([
            'test_phone' => ['required', 'regex:/^\+251[0-9]{9}$/'],
            'test_message' => ['nullable', 'string', 'max:500'],
        ]);

        if (! SmsConfig::isConfigured()) {
            return redirect()->route('super-admin.sms-settings.edit')
                ->with('error', 'Configure and save your SMS provider settings before sending a test message.');
        }

        try {
            $sms->sendTestMessage($data['test_phone'], $data['test_message'] ?? null);
        } catch (\Throwable $exception) {
            return redirect()->route('super-admin.sms-settings.edit')
                ->with('error', 'Test SMS failed: '.$exception->getMessage());
        }

        $driverLabel = SmsConfig::driver() === 'log' ? 'application log' : 'SMSEthiopia';

        return redirect()->route('super-admin.sms-settings.edit')
            ->with('status', "Test message sent via {$driverLabel} to {$data['test_phone']}.");
    }
}
