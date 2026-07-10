<?php

declare(strict_types=1);

namespace LetMeSendEmail\Responses;

final class ContactCategoryListResponse
{
    /** @var ContactCategoryResponse[] */
    private array $items;
    private PaginationInfo $pagination;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->items = array_map(
            fn (array $item) => new ContactCategoryResponse($item),
            $data['data'] ?? [],
        );
        $this->pagination = new PaginationInfo($data['pagination'] ?? []);
    }

    /** @return ContactCategoryResponse[] */
    public function items(): array
    {
        return $this->items;
    }

    public function pagination(): PaginationInfo
    {
        return $this->pagination;
    }
}
