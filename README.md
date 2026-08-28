# laravel-dcb-lk

Direct Carrier Billing for Sri Lanka - a single Laravel driver interface over
**Ideamart** and **mSpace** (both hSenid Mobile platforms), covering OTP
subscription registration, status polling, and inbound webhooks.

This is an **unofficial**, community package - not published or endorsed by
hSenid Mobile, Ideamart, or mSpace.

## Why

Neither provider ships a real Composer/Laravel package - just a "sample app"
per language on GitHub. This wraps both behind one interface, with the sharp
edges (masked subscriber IDs, inconsistent phone number formats, an
undocumented "INITIAL CHARGING PENDING" status, mSpace's OTP-is-root-level-
but-subscription-is-nested URL layout) already handled.

## Install

```bash
composer require kalindussasinindu/laravel-dcb-lk
php artisan vendor:publish --tag=dcb-lk-config
```

Add your credentials to `.env`:

```env
DCB_LK_DRIVER=ideamart

IDEAMART_APP_ID=
IDEAMART_PASSWORD=
IDEAMART_WEBHOOK_SECRET=

MSPACE_APP_ID=
MSPACE_PASSWORD=
MSPACE_WEBHOOK_SECRET=
```

See `config/dcb-lk.php` for every available option (SMS credentials, OTP
metadata defaults, base URLs).

## Usage

```php
use DcbLk\Facades\DcbLk;
use DcbLk\Support\SubscriberId;

// Uses config('dcb-lk.default') - or DcbLk::driver('mspace') for a specific one.
$response = DcbLk::requestOtp(SubscriberId::fromPhone('0771234567'));

if ($response->successful()) {
    $referenceNo = $response->get('referenceNo');
}
```

```php
$response = DcbLk::verifyOtp($referenceNo, $otpFromUser);

if ($response->successful()) {
    // Ideamart returns a masked tel:... id here - store it verbatim,
    // getStatus/send need that exact id, not the plain phone number.
    $subscriberId = $response->get('subscriberId');
}
```

```php
$status = DcbLk::getStatus($subscriberId);

if ($status->successful()) {
    $subscriptionStatus = \DcbLk\Data\SubscriptionStatus::fromCarrierString(
        $status->get('subscriptionStatus')
    );

    if ($subscriptionStatus->isActive()) {
        // grant access
    }
}
```

### Webhooks

Both carriers push subscription lifecycle changes to a URL you register on
their portal. `WebhookPayload` verifies the shared secret and parses the
payload - finding/updating your own subscriber record is up to you:

```php
use DcbLk\Webhooks\WebhookPayload;
use Illuminate\Http\Request;

Route::post('/webhooks/ideamart', function (Request $request) {
    $payload = WebhookPayload::fromRequest(
        $request,
        config('dcb-lk.drivers.ideamart.webhook_secret'),
    );

    if (!$payload->verified) {
        return response()->json(['statusCode' => 'E1001', 'statusDetail' => 'UNAUTHORIZED'], 403);
    }

    $subscription = Subscription::whereIn('subscriber_id', $payload->lookupVariants())->first();

    if ($subscription && $payload->status) {
        // e.g. $payload->status->isActive() ? grant() : revoke();
    }

    return response()->json(['statusCode' => 'S1000', 'statusDetail' => 'SUCCESS']);
});
```

### Grace periods for a `PENDING`/`TEMPORARY_BLOCKED` status

Both of those mean "might resolve on its own" (a failed charge retry, a
temporary hold), not "cut off now" - `BLOCKED`/`UNREGISTERED` are the
permanent ones (`SubscriptionStatus::isTerminal()`). Whether to keep granting
access for N days while a subscription sits in `PENDING` is a product
decision your app owns - this package just tells you which bucket a status
falls into, not what to do about it.

## Testing

```bash
composer install
composer test
```

## License

MIT.
