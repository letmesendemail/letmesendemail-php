<?php

declare(strict_types=1);

namespace LetMeSendEmail\Exceptions;

abstract class ApiException extends \RuntimeException
{
    private ?int $httpStatus = null;
    private ?string $apiCode = null;
    private array $validationErrors = [];
    private array $headers = [];
    private ?string $requestId = null;
    private ?string $rawBody = null;

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
        $exception = new static($message, $httpStatus, $previous);
        $exception->httpStatus = $httpStatus;
        $exception->apiCode = $apiCode;
        $exception->validationErrors = $validationErrors;
        $exception->headers = $headers;
        $exception->requestId = $requestId;
        $exception->rawBody = $rawBody;

        return $exception;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function getApiCode(): ?string
    {
        return $this->apiCode;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getRawBody(): ?string
    {
        return $this->rawBody;
    }
}
