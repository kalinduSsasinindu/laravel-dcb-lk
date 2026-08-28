<?php

declare(strict_types=1);

namespace DcbLk\Drivers;

class IdeamartDriver extends AbstractCarrierDriver
{
    protected function logLabel(): string
    {
        return 'Ideamart';
    }

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
        return "{$this->config['base_url']}/send";
    }

    protected function subscriptionStatusUrl(): string
    {
        return "{$this->config['base_url']}/getStatus";
    }

    protected function smsSendUrl(): string
    {
        // Distinct from base_url: SMS-send is its own Ideamart
        // application/host, not under the subscription API path.
        $base = $this->config['sms']['base_url'] ?? 'https://api.ideamart.io';

        return "{$base}/sms/send";
    }
}
