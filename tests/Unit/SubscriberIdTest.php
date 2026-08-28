<?php

declare(strict_types=1);

namespace DcbLk\Tests\Unit;

use DcbLk\Support\SubscriberId;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SubscriberIdTest extends TestCase
{
    #[DataProvider('phoneFormats')]
    public function test_from_phone_normalizes_every_common_input_shape(string $input): void
    {
        $this->assertSame('tel:94771234567', SubscriberId::fromPhone($input));
    }

    public static function phoneFormats(): array
    {
        return [
            'local 07...' => ['0771234567'],
            'local 7... (no leading 0)' => ['771234567'],
            '+94...' => ['+94771234567'],
            '0094...' => ['0094771234567'],
            '94... (no prefix)' => ['94771234567'],
            'already tel:...' => ['tel:94771234567'],
            'with spaces' => ['077 123 4567'],
        ];
    }

    public function test_from_phone_rejects_an_invalid_number(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SubscriberId::fromPhone('12345');
    }

    public function test_is_plain_msisdn(): void
    {
        $this->assertTrue(SubscriberId::isPlainMsisdn('94771234567'));
        $this->assertTrue(SubscriberId::isPlainMsisdn('tel:94771234567'));
        $this->assertFalse(SubscriberId::isPlainMsisdn('some-masked-carrier-id-xyz'));
    }

    public function test_resolve_prefers_the_carriers_own_masked_id_over_the_fallback_phone(): void
    {
        $this->assertSame(
            'tel:some-masked-id',
            SubscriberId::resolve('some-masked-id', '0771234567'),
        );
    }

    public function test_resolve_falls_back_to_the_phone_when_no_carrier_id_was_given(): void
    {
        $this->assertSame('tel:94771234567', SubscriberId::resolve('', '0771234567'));
    }

    public function test_prefer_stored_keeps_an_existing_masked_id_over_an_incoming_plain_msisdn(): void
    {
        $this->assertSame(
            'tel:some-masked-id',
            SubscriberId::preferStored('tel:some-masked-id', '94771234567'),
        );
    }

    public function test_prefer_stored_accepts_the_incoming_value_when_nothing_is_stored_yet(): void
    {
        $this->assertSame('94771234567', SubscriberId::preferStored(null, '94771234567'));
    }

    public function test_lookup_variants_covers_every_common_shape(): void
    {
        $variants = SubscriberId::lookupVariants('0771234567');

        $this->assertContains('94771234567', $variants);
        $this->assertContains('tel:94771234567', $variants);
        $this->assertContains('+94771234567', $variants);
        $this->assertContains('0771234567', $variants);
    }
}
