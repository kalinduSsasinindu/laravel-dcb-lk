<?php

declare(strict_types=1);

namespace DcbLk\Data;

/**
 * Both Ideamart and mSpace reply with the same shape - a top-level
 * statusCode ("S1000" for success, "E..." for every failure mode) plus a
 * human-readable statusDetail - wrapped here instead of leaving callers to
 * poke at raw arrays and hardcode "S1000" everywhere.
 */
final class CarrierResponse
{
    /**
     * @param  array<string, mixed>  $raw  The full decoded response body, untouched - anything not covered by a named accessor below (e.g. subscriberId, referenceNo, subscriptionStatus) is still here.
     */
    public function __construct(
        public readonly string $statusCode,
        public readonly ?string $statusDetail,
        public readonly array $raw,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            statusCode: (string) ($data['statusCode'] ?? 'E999'),
            statusDetail: $data['statusDetail'] ?? null,
            raw: $data,
        );
    }

    public function successful(): bool
    {
        return $this->statusCode === 'S1000';
    }

    public function failed(): bool
    {
        return !$this->successful();
    }

    /**
     * Read any other field the carrier included (subscriberId, referenceNo,
     * subscriptionStatus, frequency, ...) - varies by endpoint, so not
     * every field gets its own named property.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->raw[$key] ?? $default;
    }
}
