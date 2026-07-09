<?php

declare(strict_types=1);

namespace LetMeSendEmail\Responses;

final class ContactCategoryResponse
{
    private string $id;
    private string $name;
    private string $slug;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->slug = $data['slug'] ?? '';
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
}
