<?php

declare(strict_types=1);

namespace DcbLk\Support;

use InvalidArgumentException;

/**
 * Both carriers identify a subscriber by MSISDN, but not consistently:
 * Ideamart's OTP verify response returns a masked "tel:..." id that
 * getStatus/send then require verbatim (not the plain phone number), while
 * inbound webhooks can arrive as a masked tel: id, plain digits, a
 * +-prefixed number, or a local 0-prefixed number depending on the
 * carrier/event. This class exists because getting that wrong silently
 * breaks getStatus/send calls or webhook matching - there's no error, the
 * carrier just doesn't recognize the id.
 */
final class SubscriberId
{
    /**
     * Prefer whatever the carrier itself returned (already correctly
     * masked/formatted) over deriving one from a plain phone number -
     * falls back to the phone number only when the carrier didn't give
     * you one (e.g. building the very first OTP request).
     */
    public static function resolve(string $rawSubscriberId, string $fallbackPhone): string
    {
        $rawSubscriberId = trim($rawSubscriberId);

        if ($rawSubscriberId !== '' && !self::isPlainMsisdn($rawSubscriberId)) {
            return self::ensureTelPrefix($rawSubscriberId);
        }

        return self::fromPhone($fallbackPhone);
    }

    public static function isPlainMsisdn(string $subscriberId): bool
    {
        $digits = self::digitsOnly($subscriberId);

        return (bool) preg_match('/^947\d{8}$/', $digits);
    }

    public static function ensureTelPrefix(string $subscriberId): string
    {
        $subscriberId = trim($subscriberId);

        if ($subscriberId === '') {
            return $subscriberId;
        }

        return preg_match('/^tel:/i', $subscriberId) ? $subscriberId : 'tel:' . $subscriberId;
    }

    /**
     * Don't let a plain MSISDN (e.g. from a webhook that only gave digits)
     * overwrite an already-stored masked carrier id - once you have the
     * carrier's own id for a subscriber, keep it.
     */
    public static function preferStored(?string $existing, string $incoming): string
    {
        if ($existing && !self::isPlainMsisdn($existing) && self::isPlainMsisdn($incoming)) {
            return $existing;
        }

        return $incoming;
    }

    /**
     * Sri Lankan mobile number, in any common input shape (07XXXXXXXX,
     * 7XXXXXXXX, +94XXXXXXXXX, 0094XXXXXXXXX, 94XXXXXXXXX), normalized to
     * the tel:947XXXXXXXX form Ideamart/mSpace expect for the initial OTP
     * request.
     *
     * @throws InvalidArgumentException if the number isn't a valid Sri Lankan mobile number.
     */
    public static function fromPhone(string $phone): string
    {
        $digits = self::digitsOnly($phone);

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '94' . substr($digits, 1);
        } elseif (str_starts_with($digits, '7') && strlen($digits) === 9) {
            $digits = '94' . $digits;
        }

        if (!preg_match('/^947\d{8}$/', $digits)) {
            throw new InvalidArgumentException('Not a valid Sri Lankan mobile number.');
        }

        return 'tel:' . $digits;
    }

    /**
     * Every plausible format a webhook might send the same subscriber
     * under - use as a whereIn() lookup rather than requiring an exact
     * match against however you happened to have it stored.
     *
     * @return list<string>
     */
    public static function lookupVariants(string $subscriberId): array
    {
        $values = [$subscriberId];
        $digits = self::digitsOnly($subscriberId);

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '94' . substr($digits, 1);
        } elseif (str_starts_with($digits, '7') && strlen($digits) === 9) {
            $digits = '94' . $digits;
        }

        if ($digits !== '') {
            $values[] = $digits;
            $values[] = 'tel:' . $digits;
            $values[] = '+' . $digits;
            if (str_starts_with($digits, '94')) {
                $values[] = '0' . substr($digits, 2);
            }
        }

        return array_values(array_unique($values));
    }

    private static function digitsOnly(string $value): string
    {
        return preg_replace('/\D+/', '', preg_replace('/^tel:/i', '', trim($value)) ?? '') ?? '';
    }
}
