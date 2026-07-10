# Changelog

## 0.2.0 — 2026-07-10

- Removed `LetMeSendEmail::VERSION` constant. `Client::buildUserAgent()` now resolves the version from
  `Composer\InstalledVersions::getPrettyVersion()` at runtime, removing the need to bump a constant on release.
- Added `composer-runtime-api: ^2.0` dependency for `InstalledVersions` access.
- Added `SleeperInterface` / `SystemSleeper` integration to `Client` for testable retry delays.
- Added malformed 2xx response detection with `ApiError` thrown for unexpected body formats.
- Added `Attachment` and `TemplateVariable` request object integration in `EmailsResource::send()` and `sendWithTemplate()`.
- Changed `ContactsResource::update()` return type from `ContactResponse` to `ContactUpdateResponse`.
- Added configurable retries with exponential backoff and jitter.
- Added `Retry-After` HTTP-date support for rate-limit headers.
- Added `Attachment` request class with `fromPath()` and `fromContent()`.
- Added `ContactUpdateResponse` (fixture returns only `{ id }`).
- Added `RecipientResponse` and `EmailAttachmentResponse` typed response objects.
- Added case-insensitive response header normalization across all exception types.
- Preserved exact HTTP response body text in `ApiException::getRawBody()`.
- Added `DomainsResource` with `list()`, `get()`, `verify()`.
- Added `ContactsResource` with `create()`, `list()`, `get()`, `update()`, `delete()`.
- Added `ContactCategoriesResource` with `create()`, `list()`, `get()`, `update()`, `delete()`.
- Added `EmailTopicsResource` with `create()`, `list()`, `get()`, `update()`, `delete()`.
- Added typed response objects for all new resources.
- Added `StatusResponse` for delete and verify responses.
- Added `StatusResponse::getMessage()` for delete responses with message body.
- Updated `LetMeSendEmail` with `domains()`, `contacts()`, `contactCategories()`, `emailTopics()` accessors.
- **Breaking:** Replaced `Webhook::verifyPayload/verifyFromServerRequest` with `WebhookSignature::verify()`.
- **Breaking:** Renamed `WebhookVerificationError` to `WebhookVerificationException`.
- **Breaking:** Added `WebhookSigningException` for configuration-level signing errors.
- Implemented canonical webhook signing algorithm, case-insensitive header resolution.
- Fixed default API URL, user-agent, webhook header lookup.
- Added `LetMeSendEmail::VERSION` constant (later removed), `EmailResponse::isDuplicate()`.

## 0.1.0 — 2026-07-09

- Initial release.
- Emails API: send, sendWithTemplate, verify, list, get.
- Structured exception classes, HTTP transport with Guzzle, webhooks.
