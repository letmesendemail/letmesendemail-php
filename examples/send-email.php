<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use LetMeSendEmail\Configuration;
use LetMeSendEmail\LetMeSendEmail;

// Load from environment or set directly
$apiKey = getenv('LETMESENDEMAIL_API_KEY') ?: 'lms_live_...';

// Option 1: Quick setup
$client = new LetMeSendEmail(apiKey: $apiKey);

// Option 2: With custom configuration
// $client = new LetMeSendEmail(configuration: new Configuration(
//     apiKey: $apiKey,
//     baseUrl: 'https://example.com/api/v2',
//     timeout: 60,
// ));

// --- Send an email ---
try {
    $email = $client->emails()->send(
        from: 'Acme <hello@acme.com>',
        to: ['person@example.com'],
        subject: 'Welcome to letmesend.email',
        html: '<h1>Welcome!</h1><p>Thanks for signing up.</p>',
        text: 'Welcome! Thanks for signing up.',
        type: 'transactional',
    );

    echo 'Email sent!' . PHP_EOL;
    echo 'ID: ' . $email->getId() . PHP_EOL;
    echo 'Status: ' . $email->getStatus() . PHP_EOL;
    echo 'Recipients: ' . implode(', ', $email->getEmails()) . PHP_EOL;
} catch (\LetMeSendEmail\Exceptions\ValidationError $e) {
    echo 'Validation error: ' . $e->getMessage() . PHP_EOL;
    print_r($e->getValidationErrors());
} catch (\LetMeSendEmail\Exceptions\AuthenticationError $e) {
    echo 'Authentication error: ' . $e->getMessage() . PHP_EOL;
} catch (\LetMeSendEmail\Exceptions\RateLimitError $e) {
    echo 'Rate limited. Retry after ' . $e->getRetryAfter() . ' seconds.' . PHP_EOL;
} catch (\LetMeSendEmail\Exceptions\ApiException $e) {
    echo 'API error (' . $e->getHttpStatus() . '): ' . $e->getMessage() . PHP_EOL;
}

// --- Send with a template ---
try {
    $email = $client->emails()->sendWithTemplate(
        from: 'Acme <hello@acme.com>',
        to: ['person@example.com'],
        templateId: '01ARZ3NDEKTSV4RRFFQ69G5FAV',
        templateVariables: [
            ['key' => 'USER_NAME', 'type' => 'string', 'value' => 'John'],
            ['key' => 'ORDER_NUMBER', 'type' => 'number', 'value' => 12345],
        ],
        subject: 'Your order confirmation',
    );

    echo 'Template email sent! ID: ' . $email->getId() . PHP_EOL;
} catch (\LetMeSendEmail\Exceptions\ApiException $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}

// --- Verify an email address ---
try {
    $result = $client->emails()->verify('person@example.com');

    echo 'Email verification result:' . PHP_EOL;
    echo '  Status: ' . $result->getStatus() . PHP_EOL;
    echo '  Score: ' . $result->getScore() . PHP_EOL;
    echo '  Disposable: ' . ($result->isDisposable() ? 'Yes' : 'No') . PHP_EOL;
} catch (\LetMeSendEmail\Exceptions\ApiException $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}

// --- List sent emails ---
try {
    $list = $client->emails()->list(perPage: 10);

    foreach ($list->items() as $email) {
        echo $email->getId() . ' - ' . ($email->getSubject() ?? '(no subject)') . PHP_EOL;
    }

    echo 'Page: ' . $list->pagination()->getFetched() . ' of ' . $list->pagination()->getTotal() . PHP_EOL;
    echo 'Has more: ' . ($list->pagination()->hasMore() ? 'Yes' : 'No') . PHP_EOL;
} catch (\LetMeSendEmail\Exceptions\ApiException $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}

// --- Get a specific email ---
try {
    $email = $client->emails()->get('01kvv5dv472evp42a60sy4p7zx');

    echo 'Email details:' . PHP_EOL;
    echo '  ID: ' . $email->getId() . PHP_EOL;
    echo '  Status: ' . $email->getStatus() . PHP_EOL;
    echo '  Subject: ' . ($email->getSubject() ?? '(none)') . PHP_EOL;
    echo '  Recipients: ' . ($email->getRecipientsCount() ?? 0) . PHP_EOL;
} catch (\LetMeSendEmail\Exceptions\NotFoundError $e) {
    echo 'Email not found.' . PHP_EOL;
} catch (\LetMeSendEmail\Exceptions\ApiException $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
