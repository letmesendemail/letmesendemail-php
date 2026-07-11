<?php

declare(strict_types=1);

namespace LetMeSendEmail\Responses;

final class ContactUpdateResponse
{
    private string $id;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
        ];
    }
}
