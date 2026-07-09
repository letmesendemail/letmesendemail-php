<?php

declare(strict_types=1);

namespace LetMeSendEmail\Responses;

final class ContactResponse
{
    private string $id;
    private string $email;
    private ?string $firstName;
    private ?string $lastName;
    private ?string $phone;
    private bool $isGloballyUnsubscribed;
    private string $createdAt;
    private array $categories;
    private array $emailTopics;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->firstName = $data['first_name'] ?? null;
        $this->lastName = $data['last_name'] ?? null;
        $this->phone = $data['phone'] ?? null;
        $this->isGloballyUnsubscribed = $data['is_globally_unsubscribed'] ?? false;
        $this->createdAt = $data['created_at'] ?? '';
        $this->categories = $data['categories'] ?? [];
        $this->emailTopics = $data['email_topics'] ?? [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function isGloballyUnsubscribed(): bool
    {
        return $this->isGloballyUnsubscribed;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getEmailTopics(): array
    {
        return $this->emailTopics;
    }
}
