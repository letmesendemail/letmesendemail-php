<?php

declare(strict_types=1);

namespace LetMeSendEmail\Responses;

final class EmailListResponse
{
    /** @var EmailResponse[] */
    private array $items;
    private PaginationInfo $pagination;

    /**
     * @param array<string, mixed> $data
     */
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

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'data' => array_map(fn (EmailResponse $item) => $item->toArray(), $this->items()),
            'pagination' => $this->pagination()->toArray(),
        ];
    }
}
