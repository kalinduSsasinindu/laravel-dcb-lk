<?php

declare(strict_types=1);

namespace DcbLk\Contracts;

use DcbLk\Data\CarrierResponse;

interface CarrierDriver
{
    /**
     * Register (action: true) or unregister (action: false) a subscriber.
     */
    public function send(string $subscriberId, bool $register): CarrierResponse;

    public function getStatus(string $subscriberId): CarrierResponse;

    /**
     * @param  array<string, mixed>  $metadata  Overrides for the OTP request's applicationMetaData - see Support\OtpMetadata::ALLOWED_KEYS.
     */
    public function requestOtp(string $subscriberId, array $metadata = [], ?string $appHash = null): CarrierResponse;

    public function verifyOtp(string $referenceNo, string $otp): CarrierResponse;

    public function sendSms(string $subscriberId, string $message): CarrierResponse;
}
