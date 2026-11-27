<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Sms\SmsethiopiaClient;
use App\Services\SmsVerificationService;
use App\Support\SmsConfig;
use Illuminate\Console\Command;

class SmsDiagnoseCommand extends Command
{
    protected $signature = 'sms:diagnose {phone? : Optional +251… number to send a real test SMS}';

    protected $description = 'Diagnose why live SMS may fail (driver, key decrypt, outbound HTTPS)';

    public function handle(SmsethiopiaClient $client, SmsVerificationService $sms): int
    {
        $driver = SmsConfig::driver();
        $hasKey = filled(SmsConfig::apiKey());
        $corrupt = Setting::isUnreadable('sms.api_key');
        $probe = $client->probe();

        $this->table(['Check', 'Status'], [
            ['APP_URL', config('app.url')],
            ['Provider (sms.driver)', $driver.($driver === 'log' ? '  ← no real SMS' : '')],
            ['API key readable', $hasKey ? 'yes ('.(Setting::masked('sms.api_key') ?? '').')' : 'no'],
            ['API key decryptable', $corrupt ? 'NO — re-paste key (APP_KEY mismatch)' : 'yes / n/a'],
            ['SMS_VERIFY_SSL', config('events.sms.verify_ssl') ? 'true' : 'false'],
            ['Outbound smsethiopia.com', ($probe['ok'] ? 'OK' : 'FAIL').' — '.$probe['message']],
            ['isConfigured()', SmsConfig::isConfigured() ? 'yes' : 'no'],
        ]);

        if ($driver === 'log') {
            $this->warn('Provider is Log only. Set Super Admin → SMS settings → SMSEthiopia, then save.');
        }

        if ($corrupt) {
            $this->error('Encrypted sms.api_key cannot be decrypted. Paste the key again on live with the current APP_KEY.');
        }

        if (! ($probe['ok'] ?? false)) {
            $this->error('This server cannot reach smsethiopia.com. Fix firewall/SSL or set SMS_VERIFY_SSL=false then php artisan config:clear.');
        }

        $phone = $this->argument('phone');
        if (! filled($phone)) {
            $this->info('Tip: php artisan sms:diagnose +2519XXXXXXXX to send a live test.');

            return self::SUCCESS;
        }

        if (! preg_match('/^\+251[0-9]{9}$/', (string) $phone)) {
            $this->error('Phone must be +251 followed by 9 digits.');

            return self::FAILURE;
        }

        if ($driver !== 'smsethiopia' || ! $hasKey) {
            $this->error('Cannot send test until provider is SMSEthiopia and API key is readable.');

            return self::FAILURE;
        }

        try {
            $sms->sendTestMessage((string) $phone, 'Liba Events SMS diagnose test');
            $this->info('Test SMS accepted by SMSEthiopia for '.$phone);
        } catch (\Throwable $e) {
            $this->error('Test failed: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
