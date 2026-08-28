<?php

declare(strict_types=1);

namespace DcbLk\Data;

/**
 * Normalized subscription state, both providers use the same vocabulary
 * for getStatus responses and webhook payloads.
 *
 * TEMPORARY_BLOCKED is carrier terminology for a temporary hold that may
 * resolve on its own (insufficient balance, a failed charge retry, ...) -
 * distinct from BLOCKED, which is permanent, same as UNREGISTERED. Whether
 * to treat TEMPORARY_BLOCKED/PENDING as still-entitled during a grace
 * period is a policy decision for your app, not something this package
 * decides - see the README's "grace period" example.
 */
enum SubscriptionStatus: string
{
    case Registered = 'REGISTERED';
    case Pending = 'PENDING';
    case Unregistered = 'UNREGISTERED';
    case Charge = 'CHARGE';
    case TemporaryBlocked = 'TEMPORARY_BLOCKED';
    case Blocked = 'BLOCKED';

    /**
     * Maps a raw carrier status string (from getStatus or a webhook) onto
     * this enum. "INITIAL CHARGING PENDING" (seen from Ideamart) is folded
     * into Registered - it means the subscription itself is active and the
     * first charge attempt just hasn't posted yet, not that registration
     * is incomplete. An empty/missing status is treated as Registered too
     * (some getStatus responses omit it entirely on a healthy, already-
     * active subscription).
     */
    public static function fromCarrierString(?string $status): self
    {
        if ($status === null || trim($status) === '') {
            return self::Registered;
        }

        $normalized = strtoupper(trim($status));

        if (in_array($normalized, ['INITIAL CHARGING PENDING', 'INITIAL_CHARGING_PENDING'], true)) {
            return self::Registered;
        }

        return self::tryFrom(str_replace(' ', '_', $normalized)) ?? self::Pending;
    }

    public function isActive(): bool
    {
        return $this === self::Registered;
    }

    /** UNREGISTERED and BLOCKED are both permanent cutoffs, unlike TemporaryBlocked/Pending. */
    public function isTerminal(): bool
    {
        return $this === self::Unregistered || $this === self::Blocked;
    }
}
