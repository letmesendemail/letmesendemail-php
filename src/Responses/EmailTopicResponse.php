<?php

declare(strict_types=1);

namespace LetMeSendEmail\Responses;

final class EmailTopicResponse
{
    private string $id;
    private string $name;
    private string $slug;
    private ?string $description;
    private bool $autoSubscribe;
    private bool $public;
    private string $createdAt;
    private ?array $domain;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->slug = $data['slug'] ?? '';
        $this->description = $data['description'] ?? null;
        $this->autoSubscribe = $data['auto_subscribe'] ?? false;
        $this->public = $data['public'] ?? false;
        $this->createdAt = $data['created_at'] ?? '';
        $this->domain = $data['domain'] ?? null;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isAutoSubscribe(): bool
    {
        return $this->autoSubscribe;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getDomain(): ?array
    {
        return $this->domain;
    }
}
