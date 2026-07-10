<?php

declare(strict_types=1);

namespace LetMeSendEmail\Exceptions;

final class AuthorizationError extends ApiException
{
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

        return $exception;
    }
}
