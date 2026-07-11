<?php

declare(strict_types=1);

namespace LetMeSendEmail\Responses;

final class DomainListResponse
{
    /** @var DomainResponse[] */
    private array $items;
    private PaginationInfo $pagination;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->items = array_map(
            fn (array $item) => new DomainResponse($item),
            $data['data'] ?? [],
        );
        $this->pagination = new PaginationInfo($data['pagination'] ?? []);
    }

    /** @return DomainResponse[] */
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
            'data' => array_map(fn (DomainResponse $item) => $item->toArray(), $this->items()),
            'pagination' => $this->pagination()->toArray(),
        ];
    }
}
