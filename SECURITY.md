# Security Policy

This package handles carrier-billing credentials (Ideamart/mSpace `app_id`/
`password`), subscriber identifiers, and inbound webhooks that carry a
shared-secret query parameter - a bug here can leak credentials or let an
attacker forge subscription state. Please report it privately.

## Reporting a vulnerability

**Do not open a public GitHub issue for a security report.**

Preferred: use GitHub's private vulnerability reporting for this repo -
[Report a vulnerability](https://github.com/kalinduSsasinindu/laravel-dcb-lk/security/advisories/new)
(under the repo's **Security** tab). This keeps the report and any
discussion private until a fix ships.

If that's unavailable, email **kalindusasinindu153@gmail.com** with:

- A description of the issue and its impact
- Steps to reproduce, or a minimal PoC
- The package version and, if relevant, which driver (Ideamart/mSpace/a
  custom `extend()`-registered driver)

You'll get an acknowledgement within a few days. Please allow time for a
fix and a coordinated release before any public disclosure.

## Scope

In scope:

- Credential handling - `app_id`/`password` logging, redaction, or
  accidental exposure (see `AbstractCarrierDriver::post()`'s log
  redaction)
- Webhook verification - `WebhookPayload::fromRequest()`'s shared-secret
  check, timing attacks against it, or ways to bypass verification
- Subscriber ID handling - `SubscriberId` normalization or matching that
  could cause one subscriber's request/webhook to be attributed to another

Out of scope:

- Vulnerabilities in Ideamart or mSpace's own platforms/APIs - report
  those to hSenid Mobile directly, not here
- Issues that require a misconfigured consuming application (e.g. a
  webhook route registered without checking `$payload->verified`) - that's
  documented usage, not a bug in this package
- Missing hardening features (rate limiting, IP allowlisting) - open a
  normal feature request/issue for these instead

## Supported versions

Only the latest `1.x` release is supported with security fixes.
