<?php

declare(strict_types=1);

namespace DcbLk\Tests\Unit;

use DcbLk\Data\CarrierResponse;
use PHPUnit\Framework\TestCase;

final class CarrierResponseTest extends TestCase
{
    public function test_s1000_is_successful(): void
    {
        $response = CarrierResponse::fromArray(['statusCode' => 'S1000', 'referenceNo' => 'ref-1']);

        $this->assertTrue($response->successful());
        $this->assertFalse($response->failed());
        $this->assertSame('ref-1', $response->get('referenceNo'));
    }

    public function test_any_other_status_code_is_a_failure(): void
    {
        $response = CarrierResponse::fromArray(['statusCode' => 'E1001', 'statusDetail' => 'UNAUTHORIZED']);

        $this->assertTrue($response->failed());
        $this->assertFalse($response->successful());
        $this->assertSame('UNAUTHORIZED', $response->statusDetail);
    }

    public function test_a_missing_status_code_defaults_to_e999(): void
    {
        $response = CarrierResponse::fromArray([]);

        $this->assertSame('E999', $response->statusCode);
        $this->assertTrue($response->failed());
    }

    public function test_get_returns_the_default_for_a_missing_key(): void
    {
        $response = CarrierResponse::fromArray(['statusCode' => 'S1000']);

        $this->assertNull($response->get('subscriberId'));
        $this->assertSame('fallback', $response->get('subscriberId', 'fallback'));
    }

    public function test_get_reaches_any_field_not_covered_by_a_named_property(): void
    {
        $response = CarrierResponse::fromArray([
            'statusCode' => 'S1000',
            'subscriptionStatus' => 'REGISTERED',
            'frequency' => 'MONTHLY',
        ]);

        $this->assertSame('REGISTERED', $response->get('subscriptionStatus'));
        $this->assertSame('MONTHLY', $response->get('frequency'));
    }
}
