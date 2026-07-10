<?php

declare(strict_types=1);

namespace LetMeSendEmail\Responses;

final class StatusResponse
{
    private string $status;
    private ?string $message;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->status = $data['status'] ?? '';
        $this->message = $data['message'] ?? null;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
