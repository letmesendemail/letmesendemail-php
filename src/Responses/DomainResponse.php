<?php

declare(strict_types=1);

namespace LetMeSendEmail\Responses;

final class DomainResponse
{
    private string $id;
    private string $domainName;
    private string $status;
    private string $createdAt;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
        $this->domainName = $data['domain_name'] ?? '';
        $this->status = $data['status'] ?? '';
        $this->createdAt = $data['created_at'] ?? '';
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDomainName(): string
    {
        return $this->domainName;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'domain_name' => $this->getDomainName(),
            'status' => $this->getStatus(),
            'created_at' => $this->getCreatedAt(),
        ];
    }
}
