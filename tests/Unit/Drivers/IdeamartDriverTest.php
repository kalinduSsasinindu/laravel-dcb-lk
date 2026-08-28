<?php

declare(strict_types=1);

namespace DcbLk\Tests\Unit\Drivers;

use DcbLk\Drivers\IdeamartDriver;
use DcbLk\Tests\TestCase;
use Illuminate\Support\Facades\Http;

/**
 * Asserts the exact URL + payload shape for every endpoint - the part
 * most likely to have a copy-paste mistake when porting from the
 * original, app-specific IdeamartService.
 */
final class IdeamartDriverTest extends TestCase
{
    private IdeamartDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->driver = new IdeamartDriver([
            'app_id' => 'app-123',
            'password' => 'secret',
            'base_url' => 'https://api.ideamart.io/subscription',
            'version' => '1.0',
            'otp' => ['client' => 'MOBILEAPP', 'device' => 'Test', 'os' => 'web', 'app_code' => 'test-code'],
            'sms' => ['app_id' => 'sms-app-123', 'password' => 'sms-secret', 'base_url' => 'https://api.ideamart.io'],
        ]);
    }

    public function test_request_otp_hits_the_subscription_otp_request_endpoint(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 'S1000', 'referenceNo' => 'ref-1'])]);

        $response = $this->driver->requestOtp('tel:94771234567');

        $this->assertTrue($response->successful());
        $this->assertSame('ref-1', $response->get('referenceNo'));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.ideamart.io/subscription/otp/request'
                && $request['applicationId'] === 'app-123'
                && $request['password'] === 'secret'
                && $request['subscriberId'] === 'tel:94771234567'
                && $request['applicationMetaData']['client'] === 'MOBILEAPP'
                && $request['applicationMetaData']['appCode'] === 'test-code'
                && is_string($request['applicationHash']);
        });
    }

    public function test_verify_otp_hits_the_subscription_otp_verify_endpoint(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 'S1000', 'subscriberId' => 'tel:94771234567'])]);

        $response = $this->driver->verifyOtp('ref-1', '123456');

        $this->assertTrue($response->successful());

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.ideamart.io/subscription/otp/verify'
                && $request['referenceNo'] === 'ref-1'
                && $request['otp'] === '123456';
        });
    }

    public function test_send_registers_with_action_1(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 'S1000'])]);

        $this->driver->send('tel:94771234567', register: true);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.ideamart.io/subscription/send'
                && $request['action'] === '1'
                && $request['subscriberId'] === 'tel:94771234567';
        });
    }

    public function test_send_unregisters_with_action_0(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 'S1000'])]);

        $this->driver->send('tel:94771234567', register: false);

        Http::assertSent(fn ($request) => $request['action'] === '0');
    }

    public function test_get_status_hits_the_subscription_get_status_endpoint(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 'S1000', 'subscriptionStatus' => 'REGISTERED'])]);

        $response = $this->driver->getStatus('tel:94771234567');

        $this->assertSame('REGISTERED', $response->get('subscriptionStatus'));

        Http::assertSent(fn ($request) => $request->url() === 'https://api.ideamart.io/subscription/getStatus');
    }

    public function test_send_sms_uses_the_separate_sms_application_and_host(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 'S1000'])]);

        $this->driver->sendSms('tel:94771234567', 'hello');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.ideamart.io/sms/send'
                && $request['applicationId'] === 'sms-app-123'
                && $request['password'] === 'sms-secret'
                && $request['destinationAddresses'] === ['tel:94771234567'];
        });
    }

    public function test_send_sms_fails_without_dedicated_sms_credentials(): void
    {
        $driver = new IdeamartDriver([
            'app_id' => 'app-123',
            'password' => 'secret',
            'base_url' => 'https://api.ideamart.io/subscription',
        ]);

        Http::fake();

        $response = $driver->sendSms('tel:94771234567', 'hello');

        $this->assertTrue($response->failed());
        Http::assertNothingSent();
    }

    public function test_a_non_json_response_is_reported_as_a_failure_not_an_exception(): void
    {
        Http::fake(['*' => Http::response('<html>gateway error</html>', 502)]);

        $response = $this->driver->getStatus('tel:94771234567');

        $this->assertTrue($response->failed());
        $this->assertSame('E999', $response->statusCode);
    }
}
