<?php

declare(strict_types=1);

namespace LetMeSendEmail\Exceptions;

final class WebhookSigningException extends ApiException
{
    public static function fromReason(
        string $message,
        ?\Throwable $previous = null,
    ): self {
        return new self($message, 0, $previous);
    }
}
