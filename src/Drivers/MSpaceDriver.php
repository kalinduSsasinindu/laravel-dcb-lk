<?php

declare(strict_types=1);

namespace DcbLk\Drivers;

class MSpaceDriver extends AbstractCarrierDriver
{
    protected function logLabel(): string
    {
        return 'mSpace';
    }

    // mSpace's base_url is the root host only (e.g. https://api.mspace.lk)
    // - OTP endpoints are root-level while subscription-management
    // endpoints live under /subscription/*, unlike Ideamart where
    // everything (OTP included) sits under one /subscription base.

    protected function otpRequestUrl(): string
    {
        return "{$this->config['base_url']}/otp/request";
    }

    protected function otpVerifyUrl(): string
    {
        return "{$this->config['base_url']}/otp/verify";
    }

    protected function subscriptionSendUrl(): string
    {
        return "{$this->config['base_url']}/subscription/send";
    }

    protected function subscriptionStatusUrl(): string
    {
        return "{$this->config['base_url']}/subscription/getStatus";
    }

    protected function smsSendUrl(): string
    {
        return "{$this->config['base_url']}/sms/send";
    }

    /**
     * Falls back to the main app credentials if SendSMS wasn't provisioned
     * as a distinct application on the mSpace portal - Ideamart requires
     * its own SMS application instead (see AbstractCarrierDriver's default).
     */
    protected function smsCredentials(): array
    {
        $appId = $this->config['sms']['app_id'] ?? $this->config['app_id'] ?? null;
        $password = $this->config['sms']['password'] ?? $this->config['password'] ?? null;

        return [$appId, $password];
    }
}
