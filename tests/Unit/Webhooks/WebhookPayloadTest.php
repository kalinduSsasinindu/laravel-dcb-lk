<?php

declare(strict_types=1);

namespace DcbLk\Tests\Unit\Webhooks;

use DcbLk\Data\SubscriptionStatus;
use DcbLk\Webhooks\WebhookPayload;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

final class WebhookPayloadTest extends TestCase
{
    public function test_a_matching_secret_is_verified(): void
    {
        $request = Request::create('/webhooks/ideamart?secret=correct-secret', 'POST', [
            'subscriberId' => 'tel:94771234567',
            'status' => 'REGISTERED',
            'frequency' => 'MONTHLY',
        ]);

        $payload = WebhookPayload::fromRequest($request, 'correct-secret');

        $this->assertTrue($payload->verified);
        $this->assertSame('tel:94771234567', $payload->subscriberId);
        $this->assertSame(SubscriptionStatus::Registered, $payload->status);
        $this->assertSame('MONTHLY', $payload->frequency);
    }

    public function test_a_mismatched_secret_is_not_verified(): void
    {
        $request = Request::create('/webhooks/ideamart?secret=wrong', 'POST', [
            'subscriberId' => 'tel:94771234567',
        ]);

        $payload = WebhookPayload::fromRequest($request, 'correct-secret');

        $this->assertFalse($payload->verified);
    }

    public function test_a_missing_secret_query_param_is_not_verified(): void
    {
        $request = Request::create('/webhooks/ideamart', 'POST', [
            'subscriberId' => 'tel:94771234567',
        ]);

        $payload = WebhookPayload::fromRequest($request, 'correct-secret');

        $this->assertFalse($payload->verified);
    }

    public function test_an_unconfigured_secret_fails_closed_even_with_no_query_param(): void
    {
        // A webhook_secret you haven't set up yet must never be treated as
        // "skip verification" - that would let anyone who finds the route
        // URL POST a forged payload. See the regression this guards:
        // https://github.com/kalinduSsasinindu/laravel-dcb-lk (fail-open bug).
        $request = Request::create('/webhooks/ideamart', 'POST', [
            'subscriberId' => 'tel:94771234567',
        ]);

        $payload = WebhookPayload::fromRequest($request, null);

        $this->assertFalse($payload->verified);
    }

    public function test_an_unconfigured_secret_fails_closed_even_if_the_request_sends_one(): void
    {
        $request = Request::create('/webhooks/ideamart?secret=anything', 'POST', [
            'subscriberId' => 'tel:94771234567',
        ]);

        $payload = WebhookPayload::fromRequest($request, null);

        $this->assertFalse($payload->verified);
    }

    public function test_a_missing_status_field_leaves_status_null(): void
    {
        $request = Request::create('/webhooks/ideamart', 'POST', [
            'subscriberId' => 'tel:94771234567',
        ]);

        $payload = WebhookPayload::fromRequest($request, null);

        $this->assertNull($payload->status);
    }

    public function test_lookup_variants_covers_every_common_shape_of_the_stored_id(): void
    {
        $request = Request::create('/webhooks/ideamart', 'POST', [
            'subscriberId' => '0771234567',
        ]);

        $payload = WebhookPayload::fromRequest($request, null);
        $variants = $payload->lookupVariants();

        $this->assertContains('tel:94771234567', $variants);
        $this->assertContains('94771234567', $variants);
    }

    public function test_lookup_variants_is_empty_when_no_subscriber_id_was_sent(): void
    {
        $request = Request::create('/webhooks/ideamart', 'POST', []);

        $payload = WebhookPayload::fromRequest($request, null);

        $this->assertSame([], $payload->lookupVariants());
    }
}
