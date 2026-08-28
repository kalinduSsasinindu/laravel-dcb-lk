<?php

declare(strict_types=1);

namespace DcbLk\Facades;

use DcbLk\Contracts\CarrierDriver;
use DcbLk\DcbManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static CarrierDriver driver(?string $name = null)
 * @method static \DcbLk\Data\CarrierResponse send(string $subscriberId, bool $register)
 * @method static \DcbLk\Data\CarrierResponse getStatus(string $subscriberId)
 * @method static \DcbLk\Data\CarrierResponse requestOtp(string $subscriberId, array $metadata = [], ?string $appHash = null)
 * @method static \DcbLk\Data\CarrierResponse verifyOtp(string $referenceNo, string $otp)
 * @method static \DcbLk\Data\CarrierResponse sendSms(string $subscriberId, string $message)
 *
 * @see \DcbLk\DcbManager
 */
class DcbLk extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DcbManager::class;
    }
}
