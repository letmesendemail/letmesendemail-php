<?php

declare(strict_types=1);

use LetMeSendEmail\Exceptions\WebhookSigningException;
use LetMeSendEmail\Exceptions\WebhookVerificationException;
use LetMeSendEmail\Support\WebhookSignature;
use Tests\TestCase;

uses(TestCase::class);

function makeWebhookData(array $payload, string $secret, ?int $timestamp = null): array
{
    $timestamp ??= time();
    $msgId = 'web_123.web_log_123';

    $rawSecret = str_starts_with($secret, 'whsec_')
        ? substr($secret, 6)
        : $secret;

    $decodedSecret = base64_decode($rawSecret);

    $toSign = "{$msgId}.{$timestamp}." . json_encode($payload);
    $hexHash = hash_hmac('sha256', $toSign, $decodedSecret);
    $signature = base64_encode(pack('H*', $hexHash));

    return [
        'payload' => json_encode($payload),
        'headers' => [
            'webhook-id' => 'web_123',
            'webhook-log-id' => 'web_log_123',
            'webhook-timestamp' => (string) $timestamp,
            'webhook-signature' => "v1,{$signature}",
        ],
    ];
}

test('verifies a valid webhook signature', function () {
    $secret = base64_encode(random_bytes(32));
    $data = makeWebhookData(
        payload: ['event' => 'email.sent', 'data' => ['id' => 'email_123']],
        secret: $secret,
    );

    $result = WebhookSignature::verify(
        payload: $data['payload'],
        headers: $data['headers'],
        secret: $secret,
    );

    expect($result)->toBe(['event' => 'email.sent', 'data' => ['id' => 'email_123']]);
});

test('verifies with whsec_ prefixed secret', function () {
    $rawSecret = base64_encode(random_bytes(32));
    $prefixedSecret = 'whsec_' . $rawSecret;
    $data = makeWebhookData(
        payload: ['event' => 'email.delivered'],
        secret: $prefixedSecret,
    );

    $result = WebhookSignature::verify(
        payload: $data['payload'],
        headers: $data['headers'],
        secret: $prefixedSecret,
    );

    expect($result)->toBe(['event' => 'email.delivered']);
});

test('fails verification with wrong secret', function () {
    $data = makeWebhookData(
        payload: ['event' => 'email.sent'],
        secret: base64_encode(random_bytes(32)),
    );

    expect(fn () => WebhookSignature::verify(
        payload: $data['payload'],
        headers: $data['headers'],
        secret: base64_encode(random_bytes(32)),
    ))->toThrow(WebhookVerificationException::class, 'No matching webhook signature found.');
});

test('fails verification when timestamp is expired', function () {
    $secret = base64_encode(random_bytes(32));
    $oldTimestamp = time() - 600;
    $data = makeWebhookData(
        payload: ['event' => 'email.sent'],
        secret: $secret,
        timestamp: $oldTimestamp,
    );

    expect(fn () => WebhookSignature::verify(
        payload: $data['payload'],
        headers: $data['headers'],
        secret: $secret,
        tolerance: 300,
    ))->toThrow(WebhookVerificationException::class, 'Webhook timestamp is too old.');
});

test('fails verification when timestamp is too new', function () {
    $secret = base64_encode(random_bytes(32));
    $futureTimestamp = time() + 600;
    $data = makeWebhookData(
        payload: ['event' => 'email.sent'],
        secret: $secret,
        timestamp: $futureTimestamp,
    );

    expect(fn () => WebhookSignature::verify(
        payload: $data['payload'],
        headers: $data['headers'],
        secret: $secret,
        tolerance: 300,
    ))->toThrow(WebhookVerificationException::class, 'Webhook timestamp is too far in the future.');
});

test('fails verification when required headers are missing', function () {
    expect(fn () => WebhookSignature::verify(
        payload: '{}',
        headers: [],
        secret: 'test',
    ))->toThrow(WebhookVerificationException::class, 'Missing required webhook header: webhook-id.');
});

test('fails verification when timestamp is not numeric', function () {
    $secret = base64_encode(random_bytes(32));
    $data = makeWebhookData(
        payload: ['event' => 'test'],
        secret: $secret,
    );

    $data['headers']['webhook-timestamp'] = 'not-a-number';

    expect(fn () => WebhookSignature::verify(
        payload: $data['payload'],
        headers: $data['headers'],
        secret: $secret,
    ))->toThrow(WebhookVerificationException::class, 'Webhook timestamp is not numeric.');
});

test('supports multiple signatures where one v1 matches', function () {
    $secret = base64_encode(random_bytes(32));
    $data = makeWebhookData(
        payload: ['event' => 'email.sent'],
        secret: $secret,
    );

    $badSig = base64_encode(random_bytes(32));
    $data['headers']['webhook-signature'] = "v1,{$badSig} {$data['headers']['webhook-signature']}";

    $result = WebhookSignature::verify(
        payload: $data['payload'],
        headers: $data['headers'],
        secret: $secret,
    );

    expect($result)->toBe(['event' => 'email.sent']);
});

test('ignores unknown signature versions', function () {
    $secret = base64_encode(random_bytes(32));
    $data = makeWebhookData(
        payload: ['event' => 'email.sent'],
        secret: $secret,
    );

    $existingSig = $data['headers']['webhook-signature'];
    $data['headers']['webhook-signature'] = "v2,ignored v0,also-ignored {$existingSig}";

    $result = WebhookSignature::verify(
        payload: $data['payload'],
        headers: $data['headers'],
        secret: $secret,
    );

    expect($result)->toBe(['event' => 'email.sent']);
});

test('supports different header casing', function () {
    $secret = base64_encode(random_bytes(32));
    $data = makeWebhookData(
        payload: ['event' => 'email.delivered'],
        secret: $secret,
    );

    $result = WebhookSignature::verify(
        payload: $data['payload'],
        headers: [
            'Webhook-Id' => $data['headers']['webhook-id'],
            'Webhook-Log-Id' => $data['headers']['webhook-log-id'],
            'Webhook-Timestamp' => $data['headers']['webhook-timestamp'],
            'Webhook-Signature' => $data['headers']['webhook-signature'],
        ],
        secret: $secret,
    );

    expect($result)->toBe(['event' => 'email.delivered']);
});

test('supports HTTP_ server-style header keys', function () {
    $secret = base64_encode(random_bytes(32));
    $data = makeWebhookData(
        payload: ['event' => 'email.complaint'],
        secret: $secret,
    );

    $result = WebhookSignature::verify(
        payload: $data['payload'],
        headers: [
            'HTTP_WEBHOOK_ID' => $data['headers']['webhook-id'],
            'HTTP_WEBHOOK_LOG_ID' => $data['headers']['webhook-log-id'],
            'HTTP_WEBHOOK_TIMESTAMP' => $data['headers']['webhook-timestamp'],
            'HTTP_WEBHOOK_SIGNATURE' => $data['headers']['webhook-signature'],
        ],
        secret: $secret,
    );

    expect($result)->toBe(['event' => 'email.complaint']);
});

test('fails malformed json after signature verification', function () {
    $secret = base64_encode(random_bytes(32));
    $timestamp = time();

    $rawSecret = $secret;
    $decodedSecret = base64_decode($rawSecret);

    $badPayload = 'not-json-at-all';
    $toSign = "web_123.web_log_123.{$timestamp}." . $badPayload;
    $hexHash = hash_hmac('sha256', $toSign, $decodedSecret);
    $signature = base64_encode(pack('H*', $hexHash));

    $headers = [
        'webhook-id' => 'web_123',
        'webhook-log-id' => 'web_log_123',
        'webhook-timestamp' => (string) $timestamp,
        'webhook-signature' => "v1,{$signature}",
    ];

    expect(fn () => WebhookSignature::verify(
        payload: $badPayload,
        headers: $headers,
        secret: $secret,
    ))->toThrow(WebhookVerificationException::class, 'Webhook payload is not valid JSON.');
});

test('fails when secret cannot be decoded', function () {
    $secret = 'not-valid-base64!!!';

    expect(fn () => WebhookSignature::verify(
        payload: '{}',
        headers: [
            'webhook-id' => 'web_123',
            'webhook-log-id' => 'web_log_123',
            'webhook-timestamp' => (string) time(),
            'webhook-signature' => 'v1,sig',
        ],
        secret: $secret,
    ))->toThrow(WebhookSigningException::class, 'Webhook secret could not be decoded.');
});

test('fails when timestamp is zero', function () {
    $secret = base64_encode(random_bytes(32));

    expect(fn () => WebhookSignature::verify(
        payload: '{}',
        headers: [
            'webhook-id' => 'web_123',
            'webhook-log-id' => 'web_log_123',
            'webhook-timestamp' => '0',
            'webhook-signature' => 'v1,sig',
        ],
        secret: $secret,
    ))->toThrow(WebhookVerificationException::class, 'Webhook timestamp must be a positive integer.');
});

test('handles header values passed as arrays', function () {
    $secret = base64_encode(random_bytes(32));
    $data = makeWebhookData(
        payload: ['event' => 'email.delivered'],
        secret: $secret,
    );

    $result = WebhookSignature::verify(
        payload: $data['payload'],
        headers: [
            'webhook-id' => [$data['headers']['webhook-id']],
            'webhook-log-id' => [$data['headers']['webhook-log-id']],
            'webhook-timestamp' => [$data['headers']['webhook-timestamp']],
            'webhook-signature' => [$data['headers']['webhook-signature']],
        ],
        secret: $secret,
    );

    expect($result)->toBe(['event' => 'email.delivered']);
});

// --- Additional webhook timestamp and JSON validation tests ---

test('rejects decimal timestamp', function () {
    $secret = base64_encode(random_bytes(32));

    expect(fn () => WebhookSignature::verify(
        payload: '{}',
        headers: [
            'webhook-id' => 'web_123',
            'webhook-log-id' => 'web_log_123',
            'webhook-timestamp' => '1234567890.5',
            'webhook-signature' => 'v1,sig',
        ],
        secret: $secret,
    ))->toThrow(WebhookVerificationException::class, 'Webhook timestamp is not numeric.');
});

test('rejects negative tolerance', function () {
    $secret = base64_encode(random_bytes(32));
    $timestamp = time();
    $data = makeWebhookData(
        payload: ['event' => 'email.sent'],
        secret: $secret,
        timestamp: $timestamp,
    );

    expect(fn () => WebhookSignature::verify(
        payload: $data['payload'],
        headers: $data['headers'],
        secret: $secret,
        tolerance: -1,
    ))->toThrow(WebhookSigningException::class, 'Tolerance must be a non-negative integer');
});

test('rejects JSON array payload', function () {
    $secret = base64_encode(random_bytes(32));
    $timestamp = time();
    $badPayload = json_encode([1, 2, 3]);

    $rawSecret = $secret;
    $decodedSecret = base64_decode($rawSecret);
    $toSign = "web_123.web_log_123.{$timestamp}." . $badPayload;
    $hexHash = hash_hmac('sha256', $toSign, $decodedSecret);
    $signature = base64_encode(pack('H*', $hexHash));

    $headers = [
        'webhook-id' => 'web_123',
        'webhook-log-id' => 'web_log_123',
        'webhook-timestamp' => (string) $timestamp,
        'webhook-signature' => "v1,{$signature}",
    ];

    expect(fn () => WebhookSignature::verify(
        payload: $badPayload,
        headers: $headers,
        secret: $secret,
    ))->toThrow(WebhookVerificationException::class, 'Webhook payload must be a JSON object.');
});

test('rejects JSON string payload', function () {
    $secret = base64_encode(random_bytes(32));
    $timestamp = time();
    $badPayload = json_encode('just a string');

    $rawSecret = $secret;
    $decodedSecret = base64_decode($rawSecret);
    $toSign = "web_123.web_log_123.{$timestamp}." . $badPayload;
    $hexHash = hash_hmac('sha256', $toSign, $decodedSecret);
    $signature = base64_encode(pack('H*', $hexHash));

    $headers = [
        'webhook-id' => 'web_123',
        'webhook-log-id' => 'web_log_123',
        'webhook-timestamp' => (string) $timestamp,
        'webhook-signature' => "v1,{$signature}",
    ];

    expect(fn () => WebhookSignature::verify(
        payload: $badPayload,
        headers: $headers,
        secret: $secret,
    ))->toThrow(WebhookVerificationException::class, 'Webhook payload must be a JSON object.');
});

test('rejects JSON null payload', function () {
    $secret = base64_encode(random_bytes(32));
    $timestamp = time();
    $badPayload = 'null';

    $rawSecret = $secret;
    $decodedSecret = base64_decode($rawSecret);
    $toSign = "web_123.web_log_123.{$timestamp}." . $badPayload;
    $hexHash = hash_hmac('sha256', $toSign, $decodedSecret);
    $signature = base64_encode(pack('H*', $hexHash));

    $headers = [
        'webhook-id' => 'web_123',
        'webhook-log-id' => 'web_log_123',
        'webhook-timestamp' => (string) $timestamp,
        'webhook-signature' => "v1,{$signature}",
    ];

    expect(fn () => WebhookSignature::verify(
        payload: $badPayload,
        headers: $headers,
        secret: $secret,
    ))->toThrow(WebhookVerificationException::class, 'Webhook payload must be a JSON object.');
});

test('accepts empty JSON object', function () {
    $secret = base64_encode(random_bytes(32));
    $timestamp = time();
    $payload = '{}';

    $rawSecret = $secret;
    $decodedSecret = base64_decode($rawSecret);
    $toSign = "web_123.web_log_123.{$timestamp}." . $payload;
    $hexHash = hash_hmac('sha256', $toSign, $decodedSecret);
    $signature = base64_encode(pack('H*', $hexHash));

    $headers = [
        'webhook-id' => 'web_123',
        'webhook-log-id' => 'web_log_123',
        'webhook-timestamp' => (string) $timestamp,
        'webhook-signature' => "v1,{$signature}",
    ];

    $result = WebhookSignature::verify(
        payload: $payload,
        headers: $headers,
        secret: $secret,
    );

    expect($result)->toBe([]);
});

test('accepts nested JSON object', function () {
    $secret = base64_encode(random_bytes(32));
    $data = makeWebhookData(
        payload: ['event' => 'email.sent', 'data' => ['nested' => ['key' => 'value']]],
        secret: $secret,
    );

    $result = WebhookSignature::verify(
        payload: $data['payload'],
        headers: $data['headers'],
        secret: $secret,
    );

    expect($result)->toBe(['event' => 'email.sent', 'data' => ['nested' => ['key' => 'value']]]);
});
