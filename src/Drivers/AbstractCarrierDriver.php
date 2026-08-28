<?php

declare(strict_types=1);

namespace DcbLk\Drivers;

use DcbLk\Contracts\CarrierDriver;
use DcbLk\Data\CarrierResponse;
use DcbLk\Support\OtpMetadata;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class AbstractCarrierDriver implements CarrierDriver
{
    /**
     * @param  array<string, mixed>  $config  This driver's slice of config/dcb-lk.php (drivers.ideamart or drivers.mspace).
     */
    public function __construct(protected array $config)
    {
    }

    abstract protected function logLabel(): string;

    public function requestOtp(string $subscriberId, array $metadata = [], ?string $appHash = null): CarrierResponse
    {
        return $this->post($this->otpRequestUrl(), [
            'applicationId' => $this->config['app_id'],
            'password' => $this->config['password'],
            'subscriberId' => $subscriberId,
            'applicationHash' => $appHash ?: substr(md5(uniqid('', true)), 0, 15),
            'applicationMetaData' => OtpMetadata::build($metadata, $this->config['otp'] ?? []),
        ]);
    }

    public function verifyOtp(string $referenceNo, string $otp): CarrierResponse
    {
        return $this->post($this->otpVerifyUrl(), [
            'applicationId' => $this->config['app_id'],
            'password' => $this->config['password'],
            'referenceNo' => $referenceNo,
            'otp' => $otp,
        ]);
    }

    public function send(string $subscriberId, bool $register): CarrierResponse
    {
        return $this->post($this->subscriptionSendUrl(), [
            'applicationId' => $this->config['app_id'],
            'password' => $this->config['password'],
            'version' => $this->config['version'] ?? '1.0',
            'action' => $register ? '1' : '0',
            'subscriberId' => $subscriberId,
        ]);
    }

    public function getStatus(string $subscriberId): CarrierResponse
    {
        return $this->post($this->subscriptionStatusUrl(), [
            'applicationId' => $this->config['app_id'],
            'password' => $this->config['password'],
            'subscriberId' => $subscriberId,
        ]);
    }

    public function sendSms(string $subscriberId, string $message): CarrierResponse
    {
        [$smsAppId, $smsPassword] = $this->smsCredentials();

        if (!$smsAppId || !$smsPassword) {
            return CarrierResponse::fromArray([
                'statusCode' => 'E999',
                'statusDetail' => $this->logLabel() . ' SMS credentials are not configured.',
            ]);
        }

        return $this->post($this->smsSendUrl(), [
            'applicationId' => $smsAppId,
            'password' => $smsPassword,
            'message' => $message,
            'destinationAddresses' => [$subscriberId],
        ]);
    }

    /**
     * SMS-send is often provisioned as its own application, separate from
     * the subscription/OTP app - override this if your provider falls
     * back to the main app credentials instead of requiring its own
     * (mSpace does; Ideamart doesn't, see IdeamartDriver/MSpaceDriver).
     *
     * @return array{0: ?string, 1: ?string}
     */
    protected function smsCredentials(): array
    {
        return [$this->config['sms']['app_id'] ?? null, $this->config['sms']['password'] ?? null];
    }

    abstract protected function otpRequestUrl(): string;

    abstract protected function otpVerifyUrl(): string;

    abstract protected function subscriptionSendUrl(): string;

    abstract protected function subscriptionStatusUrl(): string;

    abstract protected function smsSendUrl(): string;

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function post(string $url, array $payload): CarrierResponse
    {
        $label = $this->logLabel();

        try {
            Log::info("{$label} API request: {$url}", ['payload' => array_merge($payload, ['password' => '*******'])]);

            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json', 'Accept' => 'application/json'])
                ->post($url, $payload);

            $data = $response->json();

            if (!is_array($data)) {
                Log::error("{$label} API returned a non-JSON or empty body", [
                    'url' => $url,
                    'http_status' => $response->status(),
                    'body_preview' => substr($response->body(), 0, 2000),
                ]);

                return CarrierResponse::fromArray([
                    'statusCode' => 'E999',
                    'statusDetail' => "Empty or invalid response from {$label} (HTTP {$response->status()}).",
                ]);
            }

            Log::info("{$label} API response: {$url}", ['http_status' => $response->status(), 'response' => $data]);

            return CarrierResponse::fromArray($data);
        } catch (Throwable $e) {
            Log::error("{$label} API error: " . $e->getMessage());

            return CarrierResponse::fromArray([
                'statusCode' => 'E999',
                'statusDetail' => 'Connection error: ' . $e->getMessage(),
            ]);
        }
    }
}
