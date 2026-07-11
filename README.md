# letmesend.email PHP SDK
[![Tests](https://img.shields.io/github/actions/workflow/status/letmesendemail/letmesendemail-php/test-php.yml?label=tests&style=for-the-badge&labelColor=000000)](https://github.com/letmesendemail/letmesendemail-php/actions/workflows/test-php.yml)
[![Packagist Downloads](https://img.shields.io/packagist/dt/letmesendemail/letmesendemail-php?style=for-the-badge&labelColor=000000)](https://packagist.org/packages/letmesendemail/letmesendemail-php)
[![Packagist Version](https://img.shields.io/packagist/v/letmesendemail/letmesendemail-php?style=for-the-badge&labelColor=000000)](https://packagist.org/packages/letmesendemail/letmesendemail-php)
[![License](https://img.shields.io/github/license/letmesendemail/letmesendemail-php?color=9cf&style=for-the-badge&labelColor=000000&cache=v1)](LICENSE.md)

The official PHP SDK for the [letmesend.email](https://letmesend.email/) API.

## Full Documentation

See the comprehensive [user manual](docs/docs.md) for complete documentation of
every resource, configuration option, retry behavior, error handling, webhook
verification, and detailed examples.

## Installation

```bash
composer require letmesendemail/letmesendemail-php
```

## Quick Start

```php
use LetMeSendEmail\LetMeSendEmail;

$apiKey = getenv('LETMESENDEMAIL_API_KEY');
if ($apiKey === false || $apiKey === '') {
    throw new \RuntimeException('LETMESENDEMAIL_API_KEY is not set.');
}

$client = new LetMeSendEmail(apiKey: $apiKey);

$email = $client->emails()->send(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com'],
    subject: 'Welcome',
    html: '<p>Hello from letmesend.email</p>',
);

echo $email->getId(); // "01kvv5a6xk9qd6y2egeae8w76e"
```

## Configuration

```php
use LetMeSendEmail\LetMeSendEmail;
use LetMeSendEmail\Configuration;

// Simple setup with just an API key:
$client = new LetMeSendEmail(apiKey: 'lms_live_...');

// With custom configuration:
$config = new Configuration(
    apiKey: 'lms_live_...',
    baseUrl: 'https://letmesend.email/api/v1',  // default
    timeout: 60,                                  // default: 30 seconds
);

$client = new LetMeSendEmail(configuration: $config);
```

### Options

| Option | Default | Description |
|--------|---------|-------------|
| `apiKey` | — | Your letmesend.email API key |
| `baseUrl` | `https://letmesend.email/api/v1` | API base URL override |
| `timeout` | `30` | Request timeout in seconds |
| `retries` | `0` | Maximum number of retry attempts |

## API Coverage

This SDK is built from the API fixtures in `data/api-data`.

### Implemented

| Resource | Fixture operations | SDK usage |
|----------|--------------------|-----------|
| Emails | `send`, `send-with-template`, `verify`, `list`, `show` | `$client->emails()` |
| Domains | `list`, `show`, `verify` | `$client->domains()` |
| Contacts | `store`, `list`, `show`, `update`, `delete` | `$client->contacts()` |
| Contact Categories | `store`, `list`, `show`, `update`, `delete` | `$client->contactCategories()` |
| Email Topics | `store`, `list`, `show`, `update`, `delete` | `$client->emailTopics()` |
| Error responses | `daily-quote-exceed`, `monthly-quote-exceed`, `domain-not-found`, `domain-unverified`, `email-size-exceeded` | Typed exceptions |
| Webhooks | Signature verification standard | `WebhookSignature::verify(...)` |

## Emails

### Send an email

```php
$email = $client->emails()->send(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com', 'Jane <jane@example.com>'],
    subject: 'Welcome to letmesend.email',
    html: '<h1>Welcome!</h1><p>Thanks for signing up.</p>',
    text: 'Welcome! Thanks for signing up.',
    type: 'transactional', // or 'broadcast'
    eventName: 'user.created',
    replyTo: ['support@acme.com'],
    cc: ['manager@acme.com'],
    bcc: ['archive@acme.com'],
    headers: ['X-Custom-Header' => 'value'],
    attachments: [
        [
            'name' => 'report.pdf',
            'mime' => 'application/pdf',
            'path' => 'https://storage.example.com/report.pdf',
        ],
    ],
);

echo $email->getId();         // "01kvv5a6xk9qd6y2egeae8w76e"
echo $email->getStatus();     // "pending_scan" or "accepted"
print_r($email->getEmails()); // list of all recipient addresses
```

### Attachments

You can provide attachments as raw arrays or as `Attachment` objects:

```php
use LetMeSendEmail\Requests\Attachment;

// From a URL (external file)
$attachment = Attachment::fromPath(
    name: 'report.pdf',
    path: 'https://example.com/report.pdf',
    contentId: 'optional-cid',
    contentDisposition: 'attachment', // or 'inline'
);

// From base64 content
$attachment = Attachment::fromContent(
    name: 'data.txt',
    content: base64_encode('Hello World'),
    contentId: 'optional-cid',
    contentDisposition: 'inline',
);

$email = $client->emails()->send(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com'],
    subject: 'With attachment',
    html: '<p>See attached</p>',
    attachments: [$attachment],
);
```

### Send with a template

```php
// Raw arrays:
$email = $client->emails()->sendWithTemplate(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com'],
    templateId: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
    subject: 'Your order confirmation',
    templateVariables: [
        ['key' => 'USER_NAME', 'type' => 'string', 'value' => 'John'],
        ['key' => 'ORDER_NUMBER', 'type' => 'number', 'value' => 12345],
    ],
);
```

Or use `TemplateVariable` objects:

```php
use LetMeSendEmail\Requests\TemplateVariable;

$email = $client->emails()->sendWithTemplate(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com'],
    templateId: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
    templateVariables: [
        new TemplateVariable('USER_NAME', 'string', 'John'),
        new TemplateVariable('ORDER_NUMBER', 'number', 12345),
    ],
);
```

### Idempotency

Pass an `idempotencyKey` to prevent duplicate sends on retry:

```php
$email = $client->emails()->send(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com'],
    subject: 'Your invoice',
    html: '<p>Invoice attached</p>',
    idempotencyKey: 'my-unique-key-abc123',
);

if ($email->isDuplicate()) {
    // This send was a duplicate — the original send was not re-attempted.
}
```

When the API returns a duplicate response (same `idempotencyKey` used within the expiry window), the response includes `"duplicate": true` and the `isDuplicate()` method returns `true`.

### Verify an email address

```php
$result = $client->emails()->verify('person@example.com');

echo $result->getStatus();     // "valid", "invalid", or "risky"
echo $result->getScore();      // 0-100
echo $result->hasMailbox();    // true
echo $result->isDisposable();  // false
echo $result->hasValidSyntax(); // true
```

### List emails

```php
// First page
$list = $client->emails()->list(perPage: 20);

foreach ($list->items() as $email) {
    echo $email->getId() . ' - ' . ($email->getSubject() ?? '(no subject)') . PHP_EOL;
}

// Pagination info
$pagination = $list->pagination();
echo $pagination->hasMore(); // true
echo $pagination->getTotal(); // 100

// Next page using the last item's ID
if ($list->pagination()->hasMore() && count($list->items()) > 0) {
    $items = $list->items();
    $lastId = $items[count($items) - 1]->getId();
    $nextPage = $client->emails()->list(perPage: 20, after: $lastId);
}

// Previous page using the first item's ID (from a page other than the first)
if (count($list->items()) > 0) {
    $items = $list->items();
    $firstId = $items[0]->getId();
    $prevPage = $client->emails()->list(perPage: 20, before: $firstId);
}
```

### Get a single email

```php
$email = $client->emails()->get('01kvv5dv472evp42a60sy4p7zx');

echo $email->getStatus();           // "sent", "queued", etc.
echo $email->getSubject();          // email subject
echo $email->getRecipientsCount();  // 1
echo $email->getAttachmentsCount(); // 0

// Typed recipient objects
foreach ($email->getRecipients() as $recipient) {
    echo $recipient->getEmailAddress(); // "koelpin.burdette@example.org"
    echo $recipient->getStatus();       // "queued", "delivered", "bounced"
    echo $recipient->getOpenCount();    // opens count
    echo $recipient->getClickCount();   // clicks count
}

// Typed attachment objects
foreach ($email->getAttachments() as $attachment) {
    echo $attachment->getName();          // "I9wJnL1QeUOKnsgE.png"
    echo $attachment->getMime();          // "image/png"
    echo $attachment->getSize();          // 16174079
    echo $attachment->getDownloadUrl();   // signed download URL
}
```

## Domains

### List domains

```php
$list = $client->domains()->list(perPage: 20);

foreach ($list->items() as $domain) {
    echo $domain->getId() . ' - ' . $domain->getDomainName() . ' (' . $domain->getStatus() . ')' . PHP_EOL;
}

$pagination = $list->pagination();
echo $pagination->hasMore(); // true/false
```

### Get a domain

```php
$domain = $client->domains()->get('01kvv5th65xzkwxe5avmdqbn4a');

echo $domain->getDomainName(); // "mpxlubowitz.com"
echo $domain->getStatus();     // "verified" or "pending"
```

### Verify a domain

```php
$result = $client->domains()->verify('mpxlubowitz.com');

echo $result->getStatus(); // "verified"
```

## Contacts

### Create a contact

```php
$contact = $client->contacts()->create(
    email: 'john@example.com',
    firstName: 'John',
    lastName: 'Doe',
    phone: '11231231234',
    categories: ['01kvtsch6f6e7hz543mjyjnqsp'],
);

echo $contact->getId(); // "01kvtsch6t80qpx3ncfea2r5a3"
echo $contact->getFirstName(); // "John"
```

### List contacts

```php
$list = $client->contacts()->list(perPage: 10);

foreach ($list->items() as $contact) {
    echo $contact->getEmail() . ' - ' . $contact->getFirstName() . PHP_EOL;
}
```

### Get a contact

```php
$contact = $client->contacts()->get('01kvtsch80sxnzw8cggwhh22x2');

echo $contact->getEmail();     // "mayer.elmira@example.com"
echo $contact->getFirstName(); // "Jalen"
```

### Update a contact

```php
$result = $client->contacts()->update(
    id: '01kvtsch98rxyxwaxwx2fbpsbp',
    firstName: 'John',
    lastName: 'Doe',
    syncCategories: false,
);

echo $result->getId(); // "01kvtsch98rxyxwaxwx2fbpsbp"
```

The `update()` method returns a `ContactUpdateResponse` which contains only the contact ID.

### Delete a contact

```php
$result = $client->contacts()->delete('01kvtscham9bjdwftnxxa8at1k');

echo $result->getStatus(); // "success"
```

## Contact Categories

### Create a category

```php
$category = $client->contactCategories()->create(name: 'New Name');

echo $category->getId();   // "01kvtkm3x5tpyhyw7xcnqf32rj"
echo $category->getName(); // "New Name"
echo $category->getSlug(); // "new-name"
```

### List categories

```php
$list = $client->contactCategories()->list(perPage: 20);

foreach ($list->items() as $category) {
    echo $category->getName() . ' (' . $category->getSlug() . ')' . PHP_EOL;
}
```

### Get a category

```php
$category = $client->contactCategories()->get('01kvtr2b9ztdvggdbrcjmm45nj');

echo $category->getName(); // "Category Name"
```

### Update a category

```php
$category = $client->contactCategories()->update(
    id: '01kvtkq34gc5zqbxpyw90q4sk6',
    name: 'New Name',
    slug: 'new-name',
);

echo $category->getSlug(); // "new-name"
```

### Delete a category

```php
$result = $client->contactCategories()->delete('01kvtmr3evcs2brxp2vcztd102');

echo $result->getStatus(); // "success"
```

## Email Topics

### Create a topic

```php
$topic = $client->emailTopics()->create(
    name: 'Product Updates',
    slug: 'product-updates',
    description: 'Emails for product updates',
    autoSubscribe: true,
    public: true,
    domainId: '01kvtsgfavkx5609jd3j2t6jr1', // optional
);

echo $topic->getId();   // "01kvtsgfb2rp5g1y3xdsrnk6n4"
echo $topic->getName(); // "Product Updates"
```

### List topics

```php
$list = $client->emailTopics()->list(perPage: 20);

foreach ($list->items() as $topic) {
    echo $topic->getName() . ' - ' . ($topic->getDescription() ?? '(no description)') . PHP_EOL;
}
```

### Get a topic

```php
$topic = $client->emailTopics()->get('01kvtsgf9e96nnj98nxsac751r');

echo $topic->getName(); // "Billing Notifications"
echo $topic->isAutoSubscribe(); // false
```

### Update a topic

```php
$topic = $client->emailTopics()->update(
    id: '01kvtsgfcbte0npnqkj8kep642',
    name: 'New Name',
    description: 'Updated description',
    public: true,
);

echo $topic->getName(); // "New Name"
```

### Delete a topic

```php
$result = $client->emailTopics()->delete('01kvtsgfdq4xw54vcvqw0ae68n');

echo $result->getStatus();  // "success"
echo $result->getMessage(); // "Email category deleted"
```

## Error Handling

All API errors throw exceptions that extend `LetMeSendEmail\Exceptions\ApiException`:

| HTTP Status | Exception | Description |
|-------------|-----------|-------------|
| 400, 413, 422 | `ValidationError` | Request validation failed |
| 401 | `AuthenticationError` | Invalid or missing API key |
| 403 | `AuthorizationError` | Insufficient permissions |
| 404 | `NotFoundError` | Resource not found |
| 409 | `ConflictError` | Resource conflict |
| 429 | `RateLimitError` | Rate limit exceeded |
| 500+ | `ApiError` | Server error |
| — | `NetworkError` | Connection failed |
| — | `TimeoutError` | Request timed out |
| — | `WebhookVerificationException` | Webhook verification failed |
| — | `WebhookSigningException` | Webhook signing configuration error |

```php
use LetMeSendEmail\Exceptions\ValidationError;
use LetMeSendEmail\Exceptions\AuthenticationError;
use LetMeSendEmail\Exceptions\RateLimitError;
use LetMeSendEmail\Exceptions\ApiException;

try {
    $client->emails()->send(/* ... */);
} catch (ValidationError $e) {
    echo $e->getMessage();
    print_r($e->getValidationErrors()); // field-level errors
} catch (AuthenticationError $e) {
    echo 'Check your API key.';
} catch (RateLimitError $e) {
    echo 'Retry after ' . $e->getRetryAfter() . ' seconds.';
} catch (ApiException $e) {
    echo 'HTTP ' . $e->getHttpStatus() . ': ' . $e->getMessage();
}
```

Error exceptions provide:

- `getMessage()` — human-readable error description.
- `getHttpStatus()` — HTTP status code.
- `getApiCode()` — API error code (e.g., `domain_not_found`, `daily_quota_exceeded`).
- `getValidationErrors()` — field-level validation errors, if any.
- `getHeaders()` — response headers.
- `getRequestId()` — request ID for debugging, if present in headers.
- `getRawBody()` — raw response body.

## Pagination

List endpoints return a response with a `PaginationInfo` object:

```php
$list = $client->emails()->list(perPage: 10);

// Items on this page
$emails = $list->items();

// Pagination metadata
$pag = $list->pagination();
$pag->hasMore();   // bool
$pag->getTotal();  // int
$pag->getPerPage(); // int
$pag->getFetched(); // int
```

Use cursor-based pagination:

```php
// Next page: use the last item's ID
if ($list->pagination()->hasMore() && count($list->items()) > 0) {
    $items = $list->items();
    $nextList = $client->emails()->list(after: $items[count($items) - 1]->getId());
}

// Previous page: use the first item's ID (from a page other than the first)
if (count($list->items()) > 0) {
    $items = $list->items();
    $prevList = $client->emails()->list(before: $items[0]->getId());
}
```

## Retries

The SDK can automatically retry idempotent requests on transient failures:

```php
$config = new Configuration(
    apiKey: 'lms_live_...',
    retries: 3,
);

$client = new LetMeSendEmail(configuration: $config);
```

### When retries happen

- **GET, HEAD, OPTIONS, DELETE** requests are always retryable.
- **POST, PUT, PATCH** requests are retryable only when an `Idempotency-Key` header is present.

### What is retried

- `NetworkError` and `TimeoutError` — connection or timeout failures.
- HTTP 408, 429, 500, 502, 503, 504 — retryable server / rate-limit errors.

### Backoff strategy

- **Rate-limit (429):** Uses the `Retry-After` header (delta-seconds or HTTP-date), capped at 300 seconds.
- **Other retryable errors:** Bounded exponential backoff with ±25% jitter starting at 100ms base delay. Each delay is capped at 300 seconds. Large attempt numbers are clamped to prevent overflow.

All delays are testable via the `SleeperInterface` (`LetMeSendEmail\Http\SleeperInterface`).

## Webhooks

Webhook signature verification is built in:

```php
use LetMeSendEmail\Support\WebhookSignature;
use LetMeSendEmail\Exceptions\WebhookVerificationException;
use LetMeSendEmail\Exceptions\WebhookSigningException;

$payload = file_get_contents('php://input');
if ($payload === false) {
    http_response_code(400);
    echo 'Failed to read request body.';
    exit;
}

$headers = getallheaders();
if (!is_array($headers)) {
    $headers = [];
}

$secret = getenv('LETMESENDEMAIL_WEBHOOK_SECRET');
if ($secret === false || $secret === '') {
    http_response_code(500);
    echo 'Webhook secret is not configured.';
    exit;
}

try {
    $event = WebhookSignature::verify(
        payload: $payload,
        headers: $headers,
        secret: $secret,
        tolerance: 300,
    );

    // $event contains the verified payload as an associative array
} catch (WebhookVerificationException $e) {
    http_response_code(400);
    echo 'Webhook verification failed: ' . $e->getMessage();
} catch (WebhookSigningException $e) {
    http_response_code(500);
    echo 'Webhook configuration error: ' . $e->getMessage();
}
```

The verifier reads four headers (`webhook-id`, `webhook-log-id`, `webhook-timestamp`,
`webhook-signature`), validates the timestamp against a configurable tolerance (default 300
seconds), and checks the HMAC-SHA256 signature against the decoded secret.

The signing secret may be prefixed with `whsec_`. The prefix is stripped automatically before
decoding.

The `webhook-signature` header supports multiple space-separated versioned signatures.
Only `v1` signatures are accepted; unknown versions are ignored.

## Testing

```bash
composer install
vendor/bin/pest
```

## Version Support

| PHP Version | Supported |
|-------------|-----------|
| 8.1 | Yes |
| 8.2 | Yes |
| 8.3 | Yes |
| 8.4 | Yes |
| 8.5 | Yes |

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
