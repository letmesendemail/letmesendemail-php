<?php

declare(strict_types=1);

namespace LetMeSendEmail;

use Composer\InstalledVersions;
use LetMeSendEmail\Exceptions\ApiError;
use LetMeSendEmail\Exceptions\ApiException;
use LetMeSendEmail\Exceptions\AuthenticationError;
use LetMeSendEmail\Exceptions\AuthorizationError;
use LetMeSendEmail\Exceptions\ConflictError;
use LetMeSendEmail\Exceptions\NetworkError;
use LetMeSendEmail\Exceptions\NotFoundError;
use LetMeSendEmail\Exceptions\RateLimitError;
use LetMeSendEmail\Exceptions\TimeoutError;
use LetMeSendEmail\Exceptions\ValidationError;
use LetMeSendEmail\Http\SleeperInterface;
use LetMeSendEmail\Http\SystemSleeper;
use LetMeSendEmail\Http\TransportInterface;

final class Client
{
    private const RETRYABLE_STATUSES = [408, 429, 500, 502, 503, 504];
    private const MAX_RETRY_DELAY = 300;

    private Configuration $config;
    private TransportInterface $transport;
    private SleeperInterface $sleeper;

    public function __construct(Configuration $config, TransportInterface $transport, ?SleeperInterface $sleeper = null)
    {
        $this->config = $config;
        $this->transport = $transport;
        $this->sleeper = $sleeper ?? new SystemSleeper();
    }

    public function getConfiguration(): Configuration
    {
        return $this->config;
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, ?array $body = null, array $headers = []): array
    {
        $mayRetry = $this->config->getRetries() > 0
            && (in_array($method, ['GET', 'HEAD', 'OPTIONS', 'DELETE'], true)
                || $this->hasIdempotencyKey($headers));

        $maxAttempts = $mayRetry ? $this->config->getRetries() + 1 : 1;

        $lastException = null;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            if ($attempt > 0) {
                $delayMs = $this->calculateDelay($attempt, $lastException);
                $this->sleeper->sleep($delayMs);
            }

            try {
                return $this->send($method, $path, $body, $headers);
            } catch (TimeoutError | NetworkError $e) {
                if (!$mayRetry || $attempt === $maxAttempts - 1) {
                    throw $e;
                }
                $lastException = $e;
            } catch (RateLimitError $e) {
                if (!$mayRetry || $attempt === $maxAttempts - 1) {
                    throw $e;
                }
                $lastException = $e;
            } catch (ApiException $e) {
                if (
                    !$mayRetry
                    || $attempt === $maxAttempts - 1
                    || !in_array($e->getHttpStatus(), self::RETRYABLE_STATUSES, true)
                ) {
                    throw $e;
                }
                $lastException = $e;
            }
        }

        /** @var \Throwable $lastException */
        throw $lastException ?? new NetworkError('Request failed after retries.');
    }

    private function calculateDelay(int $attempt, ?\Throwable $lastException): int
    {
        if ($lastException instanceof RateLimitError) {
            $retryAfter = $lastException->getRetryAfter();
            if ($retryAfter === null || $retryAfter <= 0) {
                throw $lastException;
            }
            if ($retryAfter > self::MAX_RETRY_DELAY) {
                throw $lastException;
            }
            return $retryAfter * 1000;
        }

        $baseMs = 100 * (2 ** ($attempt - 1));
        $jitterMs = (int) ($baseMs * (0.5 + (mt_rand() / mt_getrandmax()) * 0.5));

        return $jitterMs;
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    private function send(string $method, string $path, ?array $body = null, array $headers = []): array
    {
        $defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->config->getApiKey(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => $this->buildUserAgent(),
        ];

        $options = [
            'headers' => array_merge($defaultHeaders, $headers),
            'timeout' => $this->config->getTimeout(),
        ];

        if ($body !== null) {
            $options['body'] = $body;
        }

        $uri = $this->config->getBaseUrl() . '/' . ltrim($path, '/');

        /** @var array{status: int, headers: array<string, string|string[]>, body: mixed, rawBody?: string|null} $response */
        $response = $this->transport->request($method, $uri, $options);

        if ($response['status'] >= 400) {
            $this->handleErrorResponse($response);
        }

        // Malformed 2xx response handling
        $responseBody = $response['body'] ?? null;
        if (!is_array($responseBody) || (!empty($responseBody) && array_is_list($responseBody))) {
            $rawBody = $response['rawBody'] ?? (is_string($responseBody) ? $responseBody : json_encode($responseBody));
            throw ApiError::fromResponse(
                message: 'Malformed response body',
                httpStatus: $response['status'],
                headers: $response['headers'] ?? [],
                rawBody: $rawBody,
            );
        }

        /** @var array<string, mixed> $responseBody */
        return $responseBody;
    }

    private function buildUserAgent(): string
    {
        try {
            $version = InstalledVersions::getPrettyVersion('letmesendemail/letmesendemail-php');
        } catch (\OutOfBoundsException) {
            $version = null;
        }

        return 'letmesendemail-php/' . ltrim($version ?? 'dev', 'v');
    }

    /**
     * @param array<string, string> $headers
     */
    private function hasIdempotencyKey(array $headers): bool
    {
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'idempotency-key') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{status: int, headers: array<string, string|string[]>, body: mixed, rawBody?: string|null} $response
     */
    private function handleErrorResponse(array $response): never
    {
        $status = (int) $response['status'];
        $body = $response['body'] ?? [];
        $rawHeaders = $response['headers'] ?? [];

        // Normalize headers to lower-case for consistent access
        /** @var array<string, string> $headers */
        $headers = [];
        foreach ($rawHeaders as $key => $values) {
            $headers[strtolower((string) $key)] = is_array($values) ? ($values[0] ?? '') : $values;
        }

        // If body is not a valid associative array, wrap it
        if (!is_array($body)) {
            $body = ['_raw' => $body];
        }

        $message = is_string($body['message'] ?? null) ? $body['message'] : 'Unknown error.';
        $apiCode = is_string($body['name'] ?? null) ? $body['name'] : null;
        /** @var array<string, mixed> $validationErrors */
        $validationErrors = is_array($body['errors'] ?? null) ? $body['errors'] : [];
        $rawBody = $response['rawBody'] ?? json_encode($body);

        $requestId = $headers['x-request-id'] ?? null;

        $exception = match (true) {
            $status === 400 => ValidationError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status === 401 => AuthenticationError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status === 403 => AuthorizationError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status === 404 => NotFoundError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status === 409 => ConflictError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status === 413 => ValidationError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status === 422 => ValidationError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status === 429 => RateLimitError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status >= 500 && $status < 600 => ApiError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            default => ApiError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
        };

        throw $exception;
    }
}
