<?php

declare(strict_types=1);

namespace LetMeSendEmail\Responses;

final class EmailListResponse
{
    /** @var EmailResponse[] */
    private array $items;
    private PaginationInfo $pagination;

    public function __construct(array $data)
    {
        $this->items = array_map(
            fn (array $item) => EmailResponse::fromShowResponse($item),
            $data['data'] ?? [],
        );
        $this->pagination = new PaginationInfo($data['pagination'] ?? []);
    }

    /** @return EmailResponse[] */
    public function items(): array
    {
        return $this->items;
    }

    public function pagination(): PaginationInfo
    {
        return $this->pagination;
    }
}
