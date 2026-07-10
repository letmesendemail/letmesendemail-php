<?php

declare(strict_types=1);

namespace LetMeSendEmail\Exceptions;

abstract class ApiException extends \RuntimeException
{
    protected ?int $httpStatus = null;
    protected ?string $apiCode = null;
    /** @var array<string, mixed> */
    protected array $validationErrors = [];
    /** @var array<string, string> */
    protected array $headers = [];
    protected ?string $requestId = null;
    protected ?string $rawBody = null;

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
        throw new \BadMethodCallException('fromResponse must be called on a concrete exception class.');
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function getApiCode(): ?string
    {
        return $this->apiCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * @return array<string, string>
     */
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
