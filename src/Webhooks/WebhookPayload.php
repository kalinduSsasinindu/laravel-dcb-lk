<?php

declare(strict_types=1);

namespace DcbLk\Webhooks;

use DcbLk\Data\SubscriptionStatus;
use DcbLk\Support\SubscriberId;
use Illuminate\Http\Request;

/**
 * Both carriers push subscription lifecycle changes (renewed, unsubscribed,
 * blocked, ...) to a webhook you register on their portal - this parses
 * that inbound POST and verifies the shared-secret query param, but
 * deliberately doesn't touch your database: finding/updating the
 * subscriber that matches is your app's job (see lookupVariants() below
 * for matching against however you stored the id).
 *
 * <?php
 * // routes/api.php
 * Route::post('/webhooks/ideamart', function (Request $request) {
 *     $payload = WebhookPayload::fromRequest($request, config('dcb-lk.drivers.ideamart.webhook_secret'));
 *     if (!$payload->verified) {
 *         return response()->json(['statusCode' => 'E1001', 'statusDetail' => 'UNAUTHORIZED'], 403);
 *     }
 *     $subscription = Subscription::whereIn('subscriber_id', $payload->lookupVariants())->first();
 *     // ...apply $payload->status to your own model...
 *     return response()->json(['statusCode' => 'S1000', 'statusDetail' => 'SUCCESS']);
 * });
 */
final class WebhookPayload
{
    private function __construct(
        public readonly bool $verified,
        public readonly ?string $subscriberId,
        public readonly ?SubscriptionStatus $status,
        public readonly ?string $frequency,
        public readonly array $raw,
    ) {
    }

    public static function fromRequest(Request $request, ?string $expectedSecret): self
    {
        // Fail closed: a webhook_secret you haven't configured yet must
        // never be treated as "verification not required" - that would
        // let anyone who finds the route URL POST a fake subscriberId +
        // status and have it accepted. hash_equals() rather than === for
        // a timing-safe comparison of the actual secret.
        $verified = $expectedSecret !== null
            && $request->query('secret') !== null
            && hash_equals($expectedSecret, (string) $request->query('secret'));

        $subscriberId = $request->input('subscriberId');
        $rawStatus = $request->input('status');

        return new self(
            verified: $verified,
            subscriberId: $subscriberId,
            status: $rawStatus ? SubscriptionStatus::fromCarrierString($rawStatus) : null,
            frequency: $request->input('frequency'),
            raw: $request->all(),
        );
    }

    /**
     * Every plausible format this webhook's subscriberId might be stored
     * under - pass to whereIn() rather than requiring an exact match.
     *
     * @return list<string>
     */
    public function lookupVariants(): array
    {
        return $this->subscriberId ? SubscriberId::lookupVariants($this->subscriberId) : [];
    }
}
