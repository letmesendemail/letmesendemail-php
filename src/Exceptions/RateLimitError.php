<?php

declare(strict_types=1);

namespace LetMeSendEmail\Exceptions;

final class RateLimitError extends ApiException
{
    private ?int $retryAfter = null;
    private ?int $limit = null;
    private ?int $remaining = null;
    private ?string $resetAt = null;

    /**
     * @param array<string, mixed> $validationErrors
     * @param array<string, string> $headers
     */
    public static function fromResponse(
        string $message,
        int $httpStatus,
        ?string $apiCode = null,
        array $validationErrors = [],
        array $headers = [],
        ?string $requestId = null,
        ?string $rawBody = null,
        ?\Throwable $previous = null,
    ): static {
        $exception = new self($message, $httpStatus, $previous);
        $exception->httpStatus = $httpStatus;
        $exception->apiCode = $apiCode;
        $exception->validationErrors = $validationErrors;
        $exception->headers = $headers;
        $exception->requestId = $requestId;
        $exception->rawBody = $rawBody;

        // Case-insensitive header lookup for rate-limit headers
        $headersLower = [];
        foreach ($headers as $headerKey => $headerValue) {
            $headersLower[strtolower((string) $headerKey)] = $headerValue;
        }

        $retryAfterValue = $headersLower['retry-after'] ?? null;
        if ($retryAfterValue !== null) {
            if (is_numeric($retryAfterValue)) {
                $exception->retryAfter = (int) $retryAfterValue;
            } else {
                // HTTP-date format: Thu, 01 Dec 2026 12:00:00 GMT
                $timestamp = @strtotime($retryAfterValue);
                if ($timestamp !== false && $timestamp > time()) {
                    $exception->retryAfter = $timestamp - (int) time();
                }
            }
        }

        $limitValue = $headersLower['x-ratelimit-limit'] ?? null;
        if ($limitValue !== null) {
            $exception->limit = (int) $limitValue;
        }

        $remainingValue = $headersLower['x-ratelimit-remaining'] ?? null;
        if ($remainingValue !== null) {
            $exception->remaining = (int) $remainingValue;
        }

        $resetValue = $headersLower['x-ratelimit-reset'] ?? null;
        if ($resetValue !== null) {
            $exception->resetAt = $resetValue;
        }

        return $exception;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getRemaining(): ?int
    {
        return $this->remaining;
    }

    public function getResetAt(): ?string
    {
        return $this->resetAt;
    }
}
