<?php

declare(strict_types=1);

namespace LetMeSendEmail\Responses;

final class PaginationInfo
{
    private bool $hasMore;
    private int $perPage;
    private int $fetched;
    private int $total;

    public function __construct(array $data)
    {
        $this->hasMore = $data['has_more'] ?? false;
        $this->perPage = $data['per_page'] ?? 0;
        $this->fetched = $data['fetched'] ?? 0;
        $this->total = $data['total'] ?? 0;
    }

    public function hasMore(): bool
    {
        return $this->hasMore;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getFetched(): int
    {
        return $this->fetched;
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
