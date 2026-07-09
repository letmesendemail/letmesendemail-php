<?php

declare(strict_types=1);

namespace LetMeSendEmail\Support;

use LetMeSendEmail\Exceptions\WebhookSigningException;
use LetMeSendEmail\Exceptions\WebhookVerificationException;

final class WebhookSignature
{
    private const REQUIRED_HEADERS = [
        'webhook-id',
        'webhook-log-id',
        'webhook-timestamp',
        'webhook-signature',
    ];

    public static function verify(
        string $payload,
        array $headers,
        string $secret,
        int $tolerance = 300,
    ): array {
        $resolved = self::resolveHeaders($headers);

        foreach (self::REQUIRED_HEADERS as $header) {
            if (!isset($resolved[$header]) || $resolved[$header] === '') {
                throw WebhookVerificationException::fromReason(
                    "Missing required webhook header: {$header}.",
                );
            }
        }

        $timestamp = $resolved['webhook-timestamp'];

        if (!ctype_digit($timestamp) && !(is_numeric($timestamp) && str_contains($timestamp, '.'))) {
            throw WebhookVerificationException::fromReason('Webhook timestamp is not numeric.');
        }

        $timestampInt = (int) $timestamp;

        if ($timestampInt <= 0) {
            throw WebhookVerificationException::fromReason('Webhook timestamp must be a positive integer.');
        }

        $now = time();

        if ($timestampInt < $now - $tolerance) {
            throw WebhookVerificationException::fromReason('Webhook timestamp is too old.');
        }

        if ($timestampInt > $now + $tolerance) {
            throw WebhookVerificationException::fromReason('Webhook timestamp is too far in the future.');
        }

        $signedPayload = $resolved['webhook-id']
            . '.' . $resolved['webhook-log-id']
            . '.' . $timestamp
            . '.' . $payload;

        $rawSecret = str_starts_with($secret, 'whsec_')
            ? substr($secret, 6)
            : $secret;

        $decodedSecret = base64_decode($rawSecret, true);

        if ($decodedSecret === false || $decodedSecret === '') {
            throw WebhookSigningException::fromReason('Webhook secret could not be decoded.');
        }

        $expectedHex = hash_hmac('sha256', $signedPayload, $decodedSecret);
        $expectedSignature = base64_encode(pack('H*', $expectedHex));

        $providedSignatures = explode(' ', $resolved['webhook-signature']);
        $matchFound = false;

        foreach ($providedSignatures as $entry) {
            $entry = trim($entry);

            if (!str_contains($entry, ',')) {
                continue;
            }

            [$version, $candidate] = explode(',', $entry, 2);

            if ($version !== 'v1') {
                continue;
            }

            if (hash_equals($expectedSignature, $candidate)) {
                $matchFound = true;
                break;
            }
        }

        if (!$matchFound) {
            throw WebhookVerificationException::fromReason('No matching webhook signature found.');
        }

        $data = json_decode($payload, true);

        if (!is_array($data)) {
            throw WebhookVerificationException::fromReason('Webhook payload is not valid JSON.');
        }

        return $data;
    }

    private static function resolveHeaders(array $headers): array
    {
        $resolved = [];

        foreach ($headers as $key => $value) {
            $normalized = strtolower((string) $key);

            if (is_array($value)) {
                $value = $value[0] ?? '';
            }

            $resolved[$normalized] = (string) $value;
        }

        $httpPrefixes = [
            'webhook-id' => ['http_webhook_id'],
            'webhook-log-id' => ['http_webhook_log_id'],
            'webhook-timestamp' => ['http_webhook_timestamp'],
            'webhook-signature' => ['http_webhook_signature'],
        ];

        foreach ($httpPrefixes as $canonical => $variants) {
            if (isset($resolved[$canonical]) && $resolved[$canonical] !== '') {
                continue;
            }

            foreach ($variants as $variant) {
                if (isset($resolved[$variant]) && $resolved[$variant] !== '') {
                    $resolved[$canonical] = $resolved[$variant];
                    break;
                }
            }
        }

        return $resolved;
    }
}
