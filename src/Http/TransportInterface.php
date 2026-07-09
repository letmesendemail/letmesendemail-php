<?php

declare(strict_types=1);

namespace LetMeSendEmail\Http;

interface TransportInterface
{
    /**
     * @param array<string, mixed> $options
     * @return array{status: int, headers: array<string, string>, body: mixed}
     */
    public function request(string $method, string $uri, array $options = []): array;
}
