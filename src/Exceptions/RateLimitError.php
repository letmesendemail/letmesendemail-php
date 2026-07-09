<?php

declare(strict_types=1);

namespace LetMeSendEmail\Exceptions;

final class RateLimitError extends ApiException
{
    private ?int $retryAfter = null;
    private ?int $limit = null;
    private ?int $remaining = null;
    private ?string $resetAt = null;

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
        $exception = parent::fromResponse(
            message: $message,
            httpStatus: $httpStatus,
            apiCode: $apiCode,
            validationErrors: $validationErrors,
            headers: $headers,
            requestId: $requestId,
            rawBody: $rawBody,
            previous: $previous,
        );

        if (isset($headers['Retry-After'][0])) {
            $exception->retryAfter = (int) $headers['Retry-After'][0];
        }

        if (isset($headers['X-RateLimit-Limit'][0])) {
            $exception->limit = (int) $headers['X-RateLimit-Limit'][0];
        }

        if (isset($headers['X-RateLimit-Remaining'][0])) {
            $exception->remaining = (int) $headers['X-RateLimit-Remaining'][0];
        }

        if (isset($headers['X-RateLimit-Reset'][0])) {
            $exception->resetAt = $headers['X-RateLimit-Reset'][0];
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
