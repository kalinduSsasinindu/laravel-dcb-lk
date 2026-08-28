<?php

declare(strict_types=1);

namespace DcbLk\Tests\Unit;

use DcbLk\Data\SubscriptionStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SubscriptionStatusTest extends TestCase
{
    #[DataProvider('mappings')]
    public function test_from_carrier_string(?string $raw, SubscriptionStatus $expected): void
    {
        $this->assertSame($expected, SubscriptionStatus::fromCarrierString($raw));
    }

    public static function mappings(): array
    {
        return [
            'null (some getStatus responses omit it on an active subscription)' => [null, SubscriptionStatus::Registered],
            'empty string' => ['', SubscriptionStatus::Registered],
            'REGISTERED' => ['REGISTERED', SubscriptionStatus::Registered],
            'lowercase' => ['registered', SubscriptionStatus::Registered],
            'Ideamart "initial charging pending" quirk' => ['INITIAL CHARGING PENDING', SubscriptionStatus::Registered],
            'PENDING' => ['PENDING', SubscriptionStatus::Pending],
            'UNREGISTERED' => ['UNREGISTERED', SubscriptionStatus::Unregistered],
            'CHARGE' => ['CHARGE', SubscriptionStatus::Charge],
            'TEMPORARY_BLOCKED' => ['TEMPORARY_BLOCKED', SubscriptionStatus::TemporaryBlocked],
            'TEMPORARY BLOCKED (space instead of underscore)' => ['TEMPORARY BLOCKED', SubscriptionStatus::TemporaryBlocked],
            'BLOCKED' => ['BLOCKED', SubscriptionStatus::Blocked],
            'unknown value falls back to Pending' => ['SOMETHING_NEW', SubscriptionStatus::Pending],
        ];
    }

    public function test_is_terminal(): void
    {
        $this->assertTrue(SubscriptionStatus::Unregistered->isTerminal());
        $this->assertTrue(SubscriptionStatus::Blocked->isTerminal());
        $this->assertFalse(SubscriptionStatus::TemporaryBlocked->isTerminal());
        $this->assertFalse(SubscriptionStatus::Pending->isTerminal());
        $this->assertFalse(SubscriptionStatus::Registered->isTerminal());
    }

    public function test_is_active(): void
    {
        $this->assertTrue(SubscriptionStatus::Registered->isActive());
        $this->assertFalse(SubscriptionStatus::Pending->isActive());
    }
}
