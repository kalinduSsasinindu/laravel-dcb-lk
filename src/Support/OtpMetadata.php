<?php

declare(strict_types=1);

namespace DcbLk\Support;

/**
 * The OTP request's applicationMetaData block - both carriers accept the
 * same four keys. Defaults come from config/dcb-lk.php's per-driver otp.*
 * settings; pass overrides for anything you want to set per-request
 * instead (e.g. a real device name captured from the request).
 */
final class OtpMetadata
{
    public const ALLOWED_KEYS = ['client', 'device', 'os', 'appCode'];

    /**
     * @param  array<string, mixed>  $overrides
     * @param  array<string, mixed>  $defaults  The driver's own config['otp'] array (client/device/os/app_code).
     * @return array<string, mixed>
     */
    public static function build(array $overrides, array $defaults): array
    {
        $base = [
            'client' => $defaults['client'] ?? 'MOBILEAPP',
            'device' => $defaults['device'] ?? 'Laravel',
            'os' => $defaults['os'] ?? 'web',
            'appCode' => $defaults['app_code'] ?? null,
        ];

        $filtered = array_intersect_key($overrides, array_flip(self::ALLOWED_KEYS));

        return array_merge($base, array_filter($filtered, fn ($value) => $value !== null && $value !== ''));
    }
}
