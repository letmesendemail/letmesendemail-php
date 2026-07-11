# letmesend.email PHP SDK

The official PHP SDK for the [letmesend.email](https://letmesend.email/) API.

## Overview

This SDK provides a complete, idiomatic PHP interface to the letmesend.email API.
It supports sending transactional and broadcast emails, managing domains, contacts,
contact categories, and email topics, verifying email addresses, and verifying
webhook signatures.

All HTTP communication uses GuzzleHttp under the hood. Response parsing, error
mapping, retry handling, and pagination are built in.

## Requirements

- PHP 8.1 or later
- `composer-runtime-api` (required for User-Agent version resolution)
- `guzzlehttp/guzzle` ^7.5

Supported PHP versions: 8.1, 8.2, 8.3, 8.4, 8.5

## Installation

```bash
composer require letmesendemail/letmesendemail-php
```

## Authentication

The SDK authenticates using a bearer token (API key). Obtain your API key from
the [letmesend.email](https://letmesend.email/) dashboard.

**Recommended:** Set the API key in your environment:

```bash
export LETMESENDEMAIL_API_KEY=lms_live_your_api_key_here
```

You may also pass the key directly when creating the client (see Quick Start).

```php
$apiKey = getenv('LETMESENDEMAIL_API_KEY');
if ($apiKey === false || $apiKey === '') {
    throw new \RuntimeException('LETMESENDEMAIL_API_KEY environment variable is not set.');
}
$client = new LetMeSendEmail(apiKey: $apiKey);
```

## Quick Start

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use LetMeSendEmail\LetMeSendEmail;
use LetMeSendEmail\Exceptions\ApiException;

$apiKey = getenv('LETMESENDEMAIL_API_KEY');
if ($apiKey === false || $apiKey === '') {
    throw new \RuntimeException('LETMESENDEMAIL_API_KEY is not set.');
}

$client = new LetMeSendEmail(apiKey: $apiKey);

try {
    $email = $client->emails()->send(
        from: 'Acme <hello@acme.com>',
        to: ['person@example.com'],
        subject: 'Welcome to letmesend.email',
        html: '<h1>Welcome!</h1><p>Thanks for signing up.</p>',
    );

    echo $email->getId();     // the email ID
    echo $email->getStatus(); // "pending_scan" or "accepted"
} catch (ApiException $e) {
    echo 'Error: ' . $e->getMessage();
}
```

## Client Configuration

### Simple API-Key Client

```php
$client = new LetMeSendEmail(apiKey: 'lms_live_...');
```

### Custom Configuration

```php
use LetMeSendEmail\LetMeSendEmail;
use LetMeSendEmail\Configuration;

$config = new Configuration(
    apiKey: 'lms_live_...',
    baseUrl: 'https://letmesend.email/api/v1',  // default
    timeout: 60,                                  // seconds, default 30
    retries: 3,                                   // max retry attempts, default 0
);

$client = new LetMeSendEmail(configuration: $config);
```

### Configuration Reference

| Option | Default | Description |
|--------|---------|-------------|
| `apiKey` | — | Your letmesend.email API key (required) |
| `baseUrl` | `https://letmesend.email/api/v1` | API base URL |
| `timeout` | `30` | Request timeout in seconds |
| `retries` | `0` | Maximum retry attempts for idempotent requests |

### User-Agent

The SDK sends a `User-Agent` header in the format
`letmesendemail-php/<version>`. The version is resolved at runtime from
Composer's installed package metadata (`InstalledVersions::getPrettyVersion`).
When running outside a Composer-installed context it falls back to `dev`.

### Dependency Injection

You may inject a custom Guzzle client or a custom transport:

```php
use GuzzleHttp\Client as GuzzleClient;
use LetMeSendEmail\Http\GuzzleTransport;

$guzzle = new GuzzleClient(['timeout' => 60]);
$transport = new GuzzleTransport($guzzle);

$client = new LetMeSendEmail(
    configuration: $config,
    transport: $transport,
);
```

The `SleeperInterface` (used for retry delays) is injectable through the facade:

```php
use LetMeSendEmail\Http\SleeperInterface;

// Pass a custom SleeperInterface as the last constructor argument.
// Useful for testing retry delays deterministically.
$client = new LetMeSendEmail(
    configuration: $config,
    transport: $transport,
    sleeper: $sleeper,
);
```

## Emails

All email operations are accessed via `$client->emails()`.

### Send an Email

```php
$email = $client->emails()->send(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com', 'Jane <jane@example.com>'],
    subject: 'Welcome to letmesend.email',
    html: '<h1>Welcome!</h1><p>Thanks for signing up.</p>',
    text: 'Welcome! Thanks for signing up.',
    type: 'transactional',            // "transactional" or "broadcast"
    eventName: 'user.created',
    emailTopicId: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
    replyTo: ['support@acme.com'],
    cc: ['manager@acme.com'],
    bcc: ['archive@acme.com'],
    headers: ['X-Custom-Header' => 'value'],
);

// Response
echo $email->getId();         // "01kvv5a6xk9qd6y2egeae8w76e"
echo $email->getStatus();     // "pending_scan" or "accepted"
print_r($email->getEmails()); // list of recipient addresses
print_r($email->getRestrictedEmails()); // suppressed addresses
```

**Available fields on `EmailResponse` from send:**

- `getId()` — email ID
- `getStatus()` — delivery status
- `getEmails()` — list of recipient addresses
- `getRestrictedEmails()` — list of suppressed/restricted addresses
- `isDuplicate()` — true if this was an idempotent duplicate

### Send with a Template

```php
// Using raw arrays:
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

#### TemplateVariable Objects

For better type safety, use `TemplateVariable` objects:

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

**`TemplateVariable` constructor:** `new TemplateVariable(string $key, string $type, string|int|float $value)`

- `$key` — variable name used in the template (e.g. `USER_NAME`)
- `$type` — variable type (`string`, `number`, etc.)
- `$value` — the value to insert

### Attachments

Attachments can be provided as raw arrays or as `Attachment` objects.

#### Attachment Objects

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
```

#### Sending with Attachments

```php
$email = $client->emails()->send(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com'],
    subject: 'With attachment',
    html: '<p>See attached</p>',
    attachments: [
        Attachment::fromPath(
            name: 'report.pdf',
            path: 'https://storage.example.com/report.pdf',
        ),
        Attachment::fromContent(
            name: 'data.txt',
            content: base64_encode('Here is some file content.'),
            mime: 'text/plain',
        ),
    ],
);
```

#### Raw Array Attachments

You may also pass raw arrays instead of `Attachment` objects:

```php
$email = $client->emails()->send(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com'],
    subject: 'With raw attachment',
    html: '<p>See attached</p>',
    attachments: [
        [
            'name' => 'report.pdf',
            'mime' => 'application/pdf',
            'path' => 'https://storage.example.com/report.pdf',
        ],
    ],
);
```

**`Attachment::fromPath()` parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `name` | string | Filename shown to recipients |
| `path` | string | Public URL where the file is hosted |
| `contentId` | string|null | Content-ID for inline embedding |
| `contentDisposition` | string|null | `"attachment"` or `"inline"` |
| `mime` | string|null | MIME type (e.g. `application/pdf`) |

**`Attachment::fromContent()` parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `name` | string | Filename shown to recipients |
| `content` | string | Base64-encoded file content |
| `contentId` | string|null | Content-ID for inline embedding |
| `contentDisposition` | string|null | `"attachment"` or `"inline"` |
| `mime` | string|null | MIME type (e.g. `text/plain`) |

The `path` and `content` fields are mutually exclusive. Provide one or the other,
not both. The content must be base64-encoded. PHP's `base64_encode()` function
handles this automatically.

The optional `mime` field sets the `Content-Type` for the attachment. When set
via `Attachment::fromPath()` or `Attachment::fromContent()`, it is serialized as
`mime` in the request payload.

### Idempotency

Pass an `idempotencyKey` to prevent duplicate sends on retry. The API detects
duplicate requests within a time window and returns the original response with
`duplicate: true`.

```php
$email = $client->emails()->send(
    from: 'Acme <hello@acme.com>',
    to: ['person@example.com'],
    subject: 'Your invoice',
    html: '<p>Invoice attached</p>',
    idempotencyKey: 'my-unique-key-abc123',
);

if ($email->isDuplicate()) {
    // This send was a duplicate — the original was not re-attempted.
}
```

When an `idempotencyKey` is provided on a POST request, the request becomes
eligible for retries. Without it, POST requests are never retried.

### Verify an Email Address

```php
$result = $client->emails()->verify('person@example.com');

echo $result->getStatus();      // "valid", "invalid", or "risky"
echo $result->getScore();       // 0-100
echo $result->hasMailbox();     // true/false
echo $result->isDisposable();   // true/false
echo $result->isRoleBased();    // true/false
echo $result->isDomainExists(); // true/false
echo $result->hasValidSyntax(); // true/false
echo $result->canReceiveEmail();// true/false
echo $result->hasMxRecords();   // true/false
echo $result->getBelongsTo();   // string or null
```

**Fields on `VerifyEmailResponse`:** `email`, `score`, `status`, `domainExists`,
`disposable`, `roleBased`, `hasMailbox`, `receiveEmail`, `mxRecords`,
`validSyntax`, `belongsTo`.

### List Emails

```php
// First page, 20 items per page
$list = $client->emails()->list(perPage: 20);

foreach ($list->items() as $email) {
    echo $email->getId() . ' - ' . ($email->getSubject() ?? '(no subject)') . PHP_EOL;
}

// Pagination metadata
$pagination = $list->pagination();
echo $pagination->hasMore();  // bool
echo $pagination->getTotal(); // int (approximate total count)
echo $pagination->getPerPage(); // int
echo $pagination->getFetched(); // int (items in this page)

// Next page
$list = $client->emails()->list(perPage: 20, after: 'cursor_from_previous_page');

// Previous page
$list = $client->emails()->list(perPage: 20, before: 'cursor_from_previous_page');
```

**Fields on each `EmailResponse` item from list:** `id`, `status`, `subject`, `eventName`,
`type`, `createdAt`, `sentAt`, `recipientsCount`, `attachmentsCount`.

### Get a Single Email

```php
$email = $client->emails()->get('01kvv5dv472evp42a60sy4p7zx');

echo $email->getStatus();           // "sent", "queued", "bounced", etc.
echo $email->getSubject();          // email subject
echo $email->getType();             // "transactional" or "broadcast"
echo $email->getRecipientsCount();  // 1
echo $email->getAttachmentsCount(); // 0

// Typed recipient objects
foreach ($email->getRecipients() as $recipient) {
    echo $recipient->getEmailAddress(); // "koelpin.burdette@example.org"
    echo $recipient->getStatus();       // "queued", "delivered", "bounced"
    echo $recipient->getType();         // "to", "cc", "bcc"
    echo $recipient->getOpenCount();    // opens count
    echo $recipient->getClickCount();   // clicks count
    echo $recipient->isSuppressed();    // bool
    echo $recipient->getErrorMessage(); // failure reason or null
    echo $recipient->getDeliveredAt();  // delivery timestamp or null
}

// Typed attachment objects
foreach ($email->getAttachments() as $attachment) {
    echo $attachment->getName();          // "I9wJnL1QeUOKnsgE.png"
    echo $attachment->getMime();          // "image/png"
    echo $attachment->getSize();          // 16174079
    echo $attachment->getDownloadUrl();   // signed download URL
    echo $attachment->getContentId();     // content ID for inlines
    echo $attachment->getContentDisposition(); // "attachment" or "inline"
}
```

**`RecipientResponse` fields:** `type`, `status`, `emailAddress`, `bounceType`,
`bounceReason`, `bouncedAt`, `complaintType`, `complainedAt`, `isSuppressed`,
`suppressionReason`, `openedAt`, `openCount`, `clickedAt`, `clickCount`,
`failedAt`, `errorMessage`, `deliveredAt`, `sentAt`.

**`EmailAttachmentResponse` fields:** `id`, `name`, `mime`, `contentId`,
`contentDisposition`, `size`, `downloadUrl`.

## Domains

All domain operations are accessed via `$client->domains()`.

### List Domains

```php
$list = $client->domains()->list(perPage: 20);

foreach ($list->items() as $domain) {
    echo $domain->getId() . ' - ' . $domain->getDomainName()
        . ' (' . $domain->getStatus() . ')' . PHP_EOL;
}

$pagination = $list->pagination();
echo $pagination->hasMore(); // bool

// Next page
if ($pagination->hasMore() && count($list->items()) > 0) {
    $items = $list->items();
    $nextPage = $client->domains()->list(perPage: 20, after: $items[count($items) - 1]->getId());
}

// Previous page
if (count($list->items()) > 0) {
    $items = $list->items();
    $prevPage = $client->domains()->list(perPage: 20, before: $items[0]->getId());
}
```

### Get a Domain

```php
$domain = $client->domains()->get('01kvv5th65xzkwxe5avmdqbn4a');

echo $domain->getDomainName(); // "mpxlubowitz.com"
echo $domain->getStatus();     // "verified" or "pending"
```

### Verify a Domain

```php
$result = $client->domains()->verify('mpxlubowitz.com');

echo $result->getStatus(); // "verified"
echo $result->getMessage(); // optional message
```

## Contacts

All contact operations are accessed via `$client->contacts()`.

### Create a Contact

```php
$contact = $client->contacts()->create(
    email: 'john@example.com',
    firstName: 'John',
    lastName: 'Doe',
    phone: '11231231234',
    isGloballyUnsubscribed: false,
    categories: ['01kvtsch6f6e7hz543mjyjnqsp'],
    emailTopics: ['01kvtsch6gjgtx89z84jn8zwqg'],
);

echo $contact->getId();              // "01kvtsch6t80qpx3ncfea2r5a3"
echo $contact->getEmail();           // "john@example.com"
echo $contact->getFirstName();       // "John"
echo $contact->getLastName();        // "Doe"
echo $contact->getPhone();           // "11231231234"
echo $contact->isGloballyUnsubscribed(); // false
echo $contact->getCreatedAt();       // timestamp
```

### List Contacts

```php
$list = $client->contacts()->list(perPage: 10);

foreach ($list->items() as $contact) {
    echo $contact->getEmail() . ' - ' . $contact->getFirstName() . PHP_EOL;
}

$pagination = $list->pagination();
echo $pagination->hasMore(); // bool

// Next page
if ($pagination->hasMore() && count($list->items()) > 0) {
    $items = $list->items();
    $nextPage = $client->contacts()->list(perPage: 10, after: $items[count($items) - 1]->getId());
}

// Previous page
if (count($list->items()) > 0) {
    $items = $list->items();
    $prevPage = $client->contacts()->list(perPage: 10, before: $items[0]->getId());
}
```

### Get a Contact

```php
$contact = $client->contacts()->get('01kvtsch80sxnzw8cggwhh22x2');

echo $contact->getEmail();     // "mayer.elmira@example.com"
echo $contact->getFirstName(); // "Jalen"
```

### Update a Contact

```php
$result = $client->contacts()->update(
    id: '01kvtsch98rxyxwaxwx2fbpsbp',
    firstName: 'John',
    lastName: 'Doe',
    isGloballyUnsubscribed: false,
    phone: '11231231234',
    categories: ['01kvtsch6f6e7hz543mjyjnqsp'],
    emailTopics: ['01kvtsch6gjgtx89z84jn8zwqg'],
    syncCategories: false,
    syncEmailTopics: false,
);

echo $result->getId(); // "01kvtsch98rxyxwaxwx2fbpsbp"
```

The `update()` method returns a `ContactUpdateResponse` which contains only the
contact ID.

### Delete a Contact

```php
$result = $client->contacts()->delete('01kvtscham9bjdwftnxxa8at1k');

echo $result->getStatus();  // "success"
echo $result->getMessage(); // optional message
```

## Contact Categories

All contact-category operations are accessed via `$client->contactCategories()`.

### Create a Category

```php
$category = $client->contactCategories()->create(
    name: 'New Name',
    slug: 'new-name', // optional, auto-generated if omitted
);

echo $category->getId();   // "01kvtkm3x5tpyhyw7xcnqf32rj"
echo $category->getName(); // "New Name"
echo $category->getSlug(); // "new-name"
```

### List Categories

```php
$list = $client->contactCategories()->list(perPage: 20);

foreach ($list->items() as $category) {
    echo $category->getName() . ' (' . $category->getSlug() . ')' . PHP_EOL;
}

$pagination = $list->pagination();

// Next page
if ($pagination->hasMore() && count($list->items()) > 0) {
    $items = $list->items();
    $nextPage = $client->contactCategories()->list(perPage: 20, after: $items[count($items) - 1]->getId());
}

// Previous page
if (count($list->items()) > 0) {
    $items = $list->items();
    $prevPage = $client->contactCategories()->list(perPage: 20, before: $items[0]->getId());
}
```

### Get a Category

```php
$category = $client->contactCategories()->get('01kvtr2b9ztdvggdbrcjmm45nj');

echo $category->getName(); // "Category Name"
```

### Update a Category

```php
$category = $client->contactCategories()->update(
    id: '01kvtkq34gc5zqbxpyw90q4sk6',
    name: 'New Name',
    slug: 'new-name',
);

echo $category->getSlug(); // "new-name"
```

### Delete a Category

```php
$result = $client->contactCategories()->delete('01kvtmr3evcs2brxp2vcztd102');

echo $result->getStatus(); // "success"
```

## Email Topics

All email-topic operations are accessed via `$client->emailTopics()`.

### Create a Topic

```php
$topic = $client->emailTopics()->create(
    name: 'Product Updates',
    slug: 'product-updates',
    description: 'Emails for product updates',
    autoSubscribe: true,
    public: true,
    domainId: '01kvtsgfavkx5609jd3j2t6jr1', // optional
);

echo $topic->getId();             // "01kvtsgfb2rp5g1y3xdsrnk6n4"
echo $topic->getName();           // "Product Updates"
echo $topic->getSlug();           // "product-updates"
echo $topic->getDescription();    // "Emails for product updates"
echo $topic->isAutoSubscribe();   // true
echo $topic->isPublic();           // true
echo $topic->getDomain();         // array or null
```

### List Topics

```php
$list = $client->emailTopics()->list(perPage: 20);

foreach ($list->items() as $topic) {
    echo $topic->getName() . ' - '
        . ($topic->getDescription() ?? '(no description)') . PHP_EOL;
}

$pagination = $list->pagination();

// Next page
if ($pagination->hasMore() && count($list->items()) > 0) {
    $items = $list->items();
    $nextPage = $client->emailTopics()->list(perPage: 20, after: $items[count($items) - 1]->getId());
}

// Previous page
if (count($list->items()) > 0) {
    $items = $list->items();
    $prevPage = $client->emailTopics()->list(perPage: 20, before: $items[0]->getId());
}
```

### Get a Topic

```php
$topic = $client->emailTopics()->get('01kvtsgf9e96nnj98nxsac751r');

echo $topic->getName();           // "Billing Notifications"
echo $topic->isAutoSubscribe();   // false
echo $topic->isPublic();           // true
```

### Update a Topic

```php
$topic = $client->emailTopics()->update(
    id: '01kvtsgfcbte0npnqkj8kep642',
    name: 'New Name',
    description: 'Updated description',
    public: true,
);

echo $topic->getName(); // "New Name"
```

### Delete a Topic

```php
$result = $client->emailTopics()->delete('01kvtsgfdq4xw54vcvqw0ae68n');

echo $result->getStatus();  // "success"
echo $result->getMessage(); // "Email category deleted"
```

## Pagination

List endpoints return a response with a `PaginationInfo` object accessed via
`->pagination()`.

```php
$list = $client->emails()->list(perPage: 10);

// Items on this page
$emails = $list->items();

// Pagination metadata
$pag = $list->pagination();
$pag->hasMore();     // bool — are there more results?
$pag->getTotal();    // int — approximate total across all pages
$pag->getPerPage();  // int — items requested per page
$pag->getFetched();  // int — items in this response
```

Use cursor-based pagination:

```php
$list = $client->emails()->list(perPage: 20);

// Next page: use the last item's ID
if ($list->pagination()->hasMore() && count($list->items()) > 0) {
    $items = $list->items();
    $nextPage = $client->emails()->list(perPage: 20, after: $items[count($items) - 1]->getId());
}

// Previous page: use the first item's ID (from a page other than the first)
if (count($list->items()) > 0) {
    $items = $list->items();
    $prevPage = $client->emails()->list(perPage: 20, before: $items[0]->getId());
}
```

Set `perPage` to control page size. The `after` and `before` cursors are
typically the ID of the last or first item on the current page. Never pass
`after` and `before` together.

## Errors and Exceptions

All API errors throw exceptions that extend
`LetMeSendEmail\Exceptions\ApiException`.

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

### Error Metadata

Every `ApiException` provides:

| Method | Returns | Description |
|--------|---------|-------------|
| `getMessage()` | string | Human-readable error description |
| `getHttpStatus()` | int|null | HTTP status code |
| `getApiCode()` | string|null | API error code (e.g. `domain_not_found`) |
| `getValidationErrors()` | array | Field-level validation errors (field => messages) |
| `getHeaders()` | array | Response headers as key-value pairs |
| `getRequestId()` | string|null | Request ID for debugging |
| `getRawBody()` | string|null | Raw response body text |

`RateLimitError` additionally provides:

| Method | Returns | Description |
|--------|---------|-------------|
| `getRetryAfter()` | int|null | Seconds to wait before retrying |
| `getLimit()` | int|null | Rate-limit quota |
| `getRemaining()` | int|null | Remaining requests |
| `getResetAt()` | string|null | Reset timestamp |

### Error Handling Example

```php
use LetMeSendEmail\Exceptions\ValidationError;
use LetMeSendEmail\Exceptions\AuthenticationError;
use LetMeSendEmail\Exceptions\RateLimitError;
use LetMeSendEmail\Exceptions\ApiException;

try {
    $client->emails()->send(/* ... */);
} catch (ValidationError $e) {
    // Field-level validation failed
    echo $e->getMessage();
    print_r($e->getValidationErrors());
} catch (AuthenticationError $e) {
    // API key is invalid or missing
    echo 'Check your API key.';
} catch (RateLimitError $e) {
    // Too many requests
    echo 'Retry after ' . $e->getRetryAfter() . ' seconds.';
} catch (ApiException $e) {
    // Generic API error
    echo 'HTTP ' . $e->getHttpStatus() . ': ' . $e->getMessage();
}
```

## Timeouts and Retries

### Timeout

The default timeout is 30 seconds. Configure via the `timeout` option on
`Configuration`:

```php
$config = new Configuration(
    apiKey: 'lms_live_...',
    timeout: 60,
);
```

When a request times out, a `TimeoutError` is thrown.

### Retries

Retries are disabled by default. Enable by setting `retries` to a positive
integer:

```php
$config = new Configuration(
    apiKey: 'lms_live_...',
    retries: 3,
);

$client = new LetMeSendEmail(configuration: $config);
```

**When retries happen:**

- **GET, HEAD, OPTIONS, DELETE** requests are always retryable.
- **POST, PUT, PATCH** requests are retryable only when an `Idempotency-Key`
  header is present.

**What failures are retried:**

- `NetworkError` and `TimeoutError`
- HTTP 408, 429, 500, 502, 503, 504

**What is never retried:**

- User-initiated cancellation is not applicable in synchronous PHP.
- Non-idempotent POST/PUT/PATCH without an `Idempotency-Key`.
- Client errors (4xx) other than 408 and 429.

**Backoff strategy:**

- **Rate-limit (429):** The SDK uses the exact `Retry-After` header value
  (delta-seconds or HTTP-date). The delay is never shortened by jitter. If
  `Retry-After` exceeds 300 seconds, is zero, or is missing, the error is thrown
  immediately (no retry).
- **Other retryable errors:** Bounded exponential backoff with jitter. The base
  delay starts at 100ms and doubles with each attempt. Each delay includes
  ±25% jitter. Delays are bounded at 300 seconds maximum.

Example backoff for retries=2 (3 attempts total):

1. Attempt 0 — no delay
2. Attempt 1 — ~100ms + jitter
3. Attempt 2 — ~200ms + jitter

## Response Models

Every response object exposes getter methods for its fields and a `toArray()`
method that returns the data as a plain associative array with snake_case keys:

```php
$email = $client->emails()->get('01kvv5dv472evp42a60sy4p7zx');

// Object access via getters
echo $email->getStatus();

// Array access via toArray()
$data = $email->toArray();
echo $data['status'];
print_r($data['recipients']);
```

The `toArray()` method is available on all response models, including:

- `EmailResponse` (from send, sendWithTemplate, get; recipients and attachments contain their own `toArray()`)
- `VerifyEmailResponse`
- `EmailListResponse` (items contain `EmailResponse` with `toArray()`)
- `ContactResponse`, `ContactListResponse`, `ContactUpdateResponse`
- `DomainResponse`, `DomainListResponse`
- `ContactCategoryResponse`, `ContactCategoryListResponse`
- `EmailTopicResponse`, `EmailTopicListResponse`
- `RecipientResponse`
- `EmailAttachmentResponse`
- `PaginationInfo`
- `StatusResponse`

### Testing Retry Delays

The `SleeperInterface` (`LetMeSendEmail\Http\SleeperInterface`) is injectable
through the facade for testing retry delays deterministically:

```php
use LetMeSendEmail\Http\SleeperInterface;

class TestSleeper implements SleeperInterface
{
    public array $delays = [];

    public function sleep(int $milliseconds): void
    {
        $this->delays[] = $milliseconds;
    }
}

// Pass it as the last constructor argument
$sleeper = new TestSleeper();
$client = new LetMeSendEmail(
    configuration: $config,
    sleeper: $sleeper,
);
```

## Webhooks

Webhook signature verification is built into the SDK. Use it in your webhook
endpoint handler to verify that incoming requests are genuinely from
letmesend.email.

### Verifying a Webhook

```php
use LetMeSendEmail\Support\WebhookSignature;
use LetMeSendEmail\Exceptions\WebhookVerificationException;
use LetMeSendEmail\Exceptions\WebhookSigningException;

// Read the raw request body
$payload = file_get_contents('php://input');
if ($payload === false) {
    http_response_code(400);
    echo 'Failed to read request body.';
    exit;
}

// Get the request headers (works with PHP's getallheaders())
$headers = getallheaders();
if (!is_array($headers)) {
    $headers = [];
}

// Read the webhook signing secret from the environment
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
        tolerance: 300, // seconds, default 300
    );

    // $event contains the parsed verified payload as an associative array.
    // The event type and data depend on the API configuration.
    // Process $event according to your application's needs.
} catch (WebhookVerificationException $e) {
    http_response_code(400);
    echo 'Webhook verification failed: ' . $e->getMessage();
} catch (WebhookSigningException $e) {
    http_response_code(500);
    echo 'Webhook configuration error: ' . $e->getMessage();
}
```

### Verification Details

**Required headers:**

| Header | Description |
|--------|-------------|
| `webhook-id` | Unique webhook message identifier |
| `webhook-log-id` | Log identifier |
| `webhook-timestamp` | Unix timestamp (seconds) |
| `webhook-signature` | One or more space-separated versioned signatures |

**Header resolution:**

Headers are resolved case-insensitively. The `HTTP_` prefix convention
(e.g. `HTTP_WEBHOOK_ID`) used by some server environments is also supported.

**Signing algorithm:**

1. Strip the `whsec_` prefix from the secret, if present.
2. Base64-decode the secret.
3. Compute `HMAC-SHA256` over the string:
   `<webhook-id>.<webhook-log-id>.<webhook-timestamp>.<raw-payload>`
4. Base64-encode the raw HMAC output.
5. Compare against each `v1,` entry in the `webhook-signature` header.

**Timestamp tolerance:**

The default tolerance is 300 seconds (5 minutes). Pass a custom `tolerance`
value to adjust. The timestamp must be within `[now - tolerance, now + tolerance]`
to be accepted.

**Verification failures:**

- Missing required headers → `WebhookVerificationException`
- Non-numeric timestamp → `WebhookVerificationException` (decimal timestamps and scientific notation are rejected)
- Zero or negative timestamp → `WebhookVerificationException`
- Negative tolerance → `WebhookSigningException`
- Expired timestamp → `WebhookVerificationException`
- Future timestamp → `WebhookVerificationException`
- No matching `v1` signature → `WebhookVerificationException`
- Invalid payload JSON → `WebhookVerificationException` ("not valid JSON")
- Valid JSON that is not an object (array, string, null) → `WebhookVerificationException` ("must be a JSON object")
- Non-base64 secret → `WebhookSigningException`
- Empty decoded secret → `WebhookSigningException`

## Testing

### Running Tests

```bash
composer install
vendor/bin/pest
```

### Code Quality

```bash
# Format check
vendor/bin/php-cs-fixer fix --dry-run --diff --sequential

# Static analysis
vendor/bin/phpstan analyse
```

## Runtime Support

| PHP Version | Supported |
|-------------|-----------|
| 8.1 | Yes |
| 8.2 | Yes |
| 8.3 | Yes |
| 8.4 | Yes |
| 8.5 | Yes |

## Upgrading

### From 0.1.x to 0.2.x

- The `LetMeSendEmail::VERSION` constant was removed. The User-Agent version is
  now resolved automatically from Composer metadata.
- Retry behavior was made more conservative. 429 responses without a valid
  `Retry-After` header or with an excessive delay now throw immediately.
- `Attachment` objects are now properly serialized through `toArray()` in both
  `send()` and `sendWithTemplate()`.
- `ContactsResource::update()` now returns `ContactUpdateResponse` instead of
  `ContactResponse`.
- Malformed 2xx responses (invalid JSON, arrays, null) now throw `ApiError`
  instead of returning a partial response.

## Getting Help

- [API Documentation](https://letmesend.email/docs)
- [GitHub Repository](https://github.com/letmesendemail/letmesendemail-php)
- [Issue Tracker](https://github.com/letmesendemail/letmesendemail-php/issues)
- [Changelog](../CHANGELOG.md)
