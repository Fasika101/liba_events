<?php

namespace Tests\Unit;

use App\Services\VerifyEtClient;
use PHPUnit\Framework\TestCase;

class VerifyEtClientTest extends TestCase
{
    public function test_interpret_result_matches_expected_amount_from_receipt(): void
    {
        $client = new VerifyEtClient;

        $result = $client->interpretResult([
            'requestId' => 'req-1',
            'verification' => [
                'status' => 'success',
                'verified' => true,
            ],
            'data' => [
                [
                    'status' => 'success',
                    'verified' => true,
                    'amount' => 1500,
                    'currency' => 'ETB',
                    'settlementAccountMatch' => ['matched' => true],
                ],
            ],
        ], 1500.00);

        $this->assertTrue($result['verified']);
        $this->assertSame(1500.0, $result['amount']);
        $this->assertTrue($result['amount_matched']);
        $this->assertTrue($result['settlement_matched']);
    }

    public function test_amount_mismatch_is_detected(): void
    {
        $client = new VerifyEtClient;

        $result = $client->interpretResult([
            'data' => [
                [
                    'status' => 'success',
                    'verified' => true,
                    'amount' => '100 ETB',
                ],
            ],
        ], 250.00);

        $this->assertTrue($result['verified']);
        $this->assertSame(100.0, $result['amount']);
        $this->assertFalse($result['amount_matched']);
    }

    public function test_webhook_style_payload_amount_string(): void
    {
        $client = new VerifyEtClient;

        $result = $client->interpretResult([
            'event' => 'verification.completed',
            'requestId' => 'req-2',
            'data' => [
                'status' => 'success',
                'verified' => true,
                'amount' => '99.50',
                'currency' => 'ETB',
            ],
        ], 99.50);

        $this->assertTrue($result['amount_matched']);
    }
}
