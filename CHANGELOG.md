# Changelog

All notable changes to `laravel-dcb-lk` are documented here. This project
follows [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-08-28

Initial release.

### Added

- `DcbLk` facade / `DcbManager` driver-manager (`driver('ideamart')`,
  `driver('mspace')`, or the configured default via `config('dcb-lk.default')`).
- Built-in `IdeamartDriver` and `MSpaceDriver`, both extending a shared
  `AbstractCarrierDriver` (HTTP calls, password-redacted logging, non-JSON
  response handling).
- `SubscriberId` - normalizes every common Sri Lankan mobile number shape
  (07XXXXXXXX, 7XXXXXXXX, +94..., 0094..., 94..., an already-`tel:`-prefixed
  masked id) and resolves which id to use for a given call.
- `SubscriptionStatus` enum covering `REGISTERED`/`PENDING`/`UNREGISTERED`/
  `CHARGE`/`TEMPORARY_BLOCKED`/`BLOCKED`, including Ideamart's undocumented
  `INITIAL CHARGING PENDING` quirk.
- `CarrierResponse` - typed wrapper over the `{statusCode, statusDetail, ...}`
  response shape both providers share.
- `WebhookPayload::fromRequest()` - parses and verifies inbound subscription
  webhooks via a shared-secret query parameter.
- `DcbManager::extend()` - register a custom `CarrierDriver` implementation
  (a different provider, an alternate implementation of an existing one, a
  test double) without forking the package; can also override a built-in
  driver name.
- Full Orchestra Testbench test suite (drivers, manager, webhook parsing,
  subscriber id normalization, status mapping).
