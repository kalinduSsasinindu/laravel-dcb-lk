<?php

declare(strict_types=1);

namespace DcbLk\Tests\Unit\Drivers;

use DcbLk\Drivers\MSpaceDriver;
use DcbLk\Tests\TestCase;
use Illuminate\Support\Facades\Http;

/**
 * Mirrors IdeamartDriverTest, but pins down the layout mSpace does
 * differently: OTP endpoints at the base_url root, subscription-management
 * endpoints nested under /subscription, and SMS credentials falling back
 * to the main app_id/password instead of requiring a dedicated SMS app.
 */
final class MSpaceDriverTest extends TestCase
{
    private MSpaceDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = new MSpaceDriver([
            'app_id' => 'app-123',
            'password' => 'secret',
            'base_url' => 'https://api.mspace.lk',
            'version' => '1.0',
            'otp' => ['client' => 'MOBILEAPP', 'device' => 'Test', 'os' => 'web', 'app_code' => 'test-code'],
        ]);
    }

    public function test_request_otp_hits_the_root_level_otp_request_endpoint(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 'S1000', 'referenceNo' => 'ref-1'])]);

        $response = $this->driver->requestOtp('94771234567');

        $this->assertTrue($response->successful());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.mspace.lk/otp/request'
                && $request['applicationId'] === 'app-123'
                && $request['subscriberId'] === '94771234567';
        });
    }

    public function test_verify_otp_hits_the_root_level_otp_verify_endpoint(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 'S1000'])]);

        $this->driver->verifyOtp('ref-1', '123456');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.mspace.lk/otp/verify');
    }

    public function test_send_hits_the_nested_subscription_send_endpoint(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 'S1000'])]);

        $this->driver->send('94771234567', register: true);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.mspace.lk/subscription/send'
                && $request['action'] === '1';
        });
    }

    public function test_get_status_hits_the_nested_subscription_get_status_endpoint(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 'S1000', 'subscriptionStatus' => 'REGISTERED'])]);

        $this->driver->getStatus('94771234567');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.mspace.lk/subscription/getStatus');
    }

    public function test_send_sms_falls_back_to_the_main_app_credentials_when_no_dedicated_sms_app_is_configured(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 'S1000'])]);

        $response = $this->driver->sendSms('94771234567', 'hello');

        $this->assertTrue($response->successful());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.mspace.lk/sms/send'
                && $request['applicationId'] === 'app-123'
                && $request['password'] === 'secret';
        });
    }

    public function test_send_sms_prefers_a_dedicated_sms_app_when_one_is_configured(): void
    {
        $driver = new MSpaceDriver([
            'app_id' => 'app-123',
            'password' => 'secret',
            'base_url' => 'https://api.mspace.lk',
            'sms' => ['app_id' => 'sms-app-123', 'password' => 'sms-secret'],
        ]);

        Http::fake(['*' => Http::response(['statusCode' => 'S1000'])]);

        $driver->sendSms('94771234567', 'hello');

        Http::assertSent(fn ($request) => $request['applicationId'] === 'sms-app-123' && $request['password'] === 'sms-secret');
    }
}
