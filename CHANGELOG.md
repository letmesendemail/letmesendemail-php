# Changelog

## 0.3.0 — 2026-07-09

- Added `DomainsResource` with `list()`, `get()`, `verify()`.
- Added `ContactsResource` with `create()`, `list()`, `get()`, `update()`, `delete()`.
- Added `ContactCategoriesResource` with `create()`, `list()`, `get()`, `update()`, `delete()`.
- Added `EmailTopicsResource` with `create()`, `list()`, `get()`, `update()`, `delete()`.
- Added typed response objects for all new resources.
- Added `StatusResponse` for delete and verify responses.
- Added `StatusResponse::getMessage()` for delete responses with message body.
- Updated `LetMeSendEmail` with `domains()`, `contacts()`, `contactCategories()`, `emailTopics()` accessors.
- Updated README with usage examples for all new resources.

## 0.2.0 — 2026-07-09

- **Breaking:** Replaced `Webhook::verifyPayload/verifyFromServerRequest` with `WebhookSignature::verify()`.
- **Breaking:** Renamed `WebhookVerificationError` to `WebhookVerificationException`.
- **Breaking:** Added `WebhookSigningException` for configuration-level signing errors.
- Implemented canonical webhook signing algorithm: `webhook-id.webhook-log-id.timestamp.payload` HMAC-SHA256, base64-encoded, with `v1,<sig>` versioned signature header format.
- Added support for `whsec_`-prefixed secrets with base64 decoding.
- Added tolerance check in both directions (too old / too far in the future).
- Added support for multiple space-separated versioned signatures; unknown versions are ignored.
- Added robust case-insensitive and `HTTP_`-style header resolution.

## 0.1.1 — 2026-07-09

- Fixed default API URL to `https://letmesend.email/api/v1`.
- Fixed user-agent to include package slug and SDK version.
- Added `LetMeSendEmail::VERSION` constant.
- Added `EmailResponse::isDuplicate()` for idempotent send detection.
- Fixed webhook header lookup to be case-insensitive and support `HTTP_`-prefixed server headers.
- Added `.php-cs-fixer.cache` to `.gitignore`.

## 0.1.0 — 2026-07-09

- Initial release.
- Emails API: send, sendWithTemplate, verify, list, get.
- Structured exception classes.
- HTTP transport with Guzzle.
- Webhook signature verification.
- Configuration with API key, base URL override, and timeout.
