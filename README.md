# letmesend.email PHP SDK
[![Tests](https://img.shields.io/github/actions/workflow/status/letmesendemail/letmesendemail-php/test-php.yml?label=tests&style=for-the-badge&labelColor=000000)](https://github.com/letmesendemail/letmesendemail-php/actions/workflows/test-php.yml)
[![Packagist Downloads](https://img.shields.io/packagist/dt/letmesendemail/letmesendemail-php?style=for-the-badge&labelColor=000000)](https://packagist.org/packages/letmesendemail/letmesendemail-php)
[![Packagist Version](https://img.shields.io/packagist/v/letmesendemail/letmesendemail-php?style=for-the-badge&labelColor=000000)](https://packagist.org/packages/letmesendemail/letmesendemail-php)
[![License](https://img.shields.io/github/license/letmesendemail/letmesendemail-php?color=9cf&style=for-the-badge&labelColor=000000&cache=v1)](https://github.com/letmesendemail/letmesendemail-php/blob/master/LICENSE)

The official PHP SDK for the [letmesend.email](https://letmesend.email/) API.

## Installation

```bash
composer require letmesendemail/letmesendemail-php
```

## Quick Start

```php
use LetMeSendEmail\LetMeSendEmail;

$client = new LetMeSendEmail(apiKey: $_ENV['LETMESENDEMAIL_API_KEY']);

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
            'download_url' => 'https://...',
        ],
    ],
);

echo $email->getId();         // "01kvv5a6xk9qd6y2egeae8w76e"
echo $email->getStatus();     // "pending_scan" or "accepted"
print_r($email->getEmails()); // list of all recipient addresses
```

### Send with a template

```php
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

// Next page using cursor
$list = $client->emails()->list(perPage: 20, after: 'cursor_from_previous_page');

// Previous page using cursor
$list = $client->emails()->list(perPage: 20, before: 'cursor_from_previous_page');
```

### Get a single email

```php
$email = $client->emails()->get('01kvv5dv472evp42a60sy4p7zx');

echo $email->getStatus();           // "sent", "queued", etc.
echo $email->getSubject();          // email subject
echo $email->getRecipientsCount();  // 1
print_r($email->getRecipients());   // recipient details
print_r($email->getAttachments());  // attachment details
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
$contact = $client->contacts()->update(
    id: '01kvtsch98rxyxwaxwx2fbpsbp',
    firstName: 'John',
    lastName: 'Doe',
    syncCategories: false,
);

echo $contact->getId(); // "01kvtsch98rxyxwaxwx2fbpsbp"
```

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

List endpoints return an `EmailListResponse` with a `PaginationInfo` object:

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
// After a specific item
$list = $client->emails()->list(after: $cursor);

// Before a specific item
$list = $client->emails()->list(before: $cursor);
```

## Webhooks

Webhook signature verification is built in:

```php
use LetMeSendEmail\Support\WebhookSignature;

$payload = file_get_contents('php://input');
$headers = getallheaders();

try {
    $event = WebhookSignature::verify(
        payload: $payload,
        headers: $headers,
        secret: $_ENV['LETMESENDEMAIL_WEBHOOK_SECRET'],
        tolerance: 300,
    );

    // $event contains the parsed webhook payload
    switch ($event['event'] ?? '') {
        case 'email.delivered':
            // handle delivery
            break;
        case 'email.bounced':
            // handle bounce
            break;
    }
} catch (\LetMeSendEmail\Exceptions\WebhookVerificationException $e) {
    http_response_code(400);
    echo 'Webhook verification failed: ' . $e->getMessage();
} catch (\LetMeSendEmail\Exceptions\WebhookSigningException $e) {
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
