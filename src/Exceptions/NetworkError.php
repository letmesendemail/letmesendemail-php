<?php

declare(strict_types=1);

namespace LetMeSendEmail\Exceptions;

final class NetworkError extends ApiException
{
    public static function fromRequest(
        string $message,
        ?\Throwable $previous = null,
    ): self {
        return new self($message, 0, $previous);
    }
}
