<?php

declare(strict_types=1);

namespace LetMeSendEmail\Http;

interface TransportInterface
{
    /**
     * @param array<string, mixed> $options
     * @return array{status: int, headers: array<string, array<string>>, body: mixed, rawBody: ?string}
     */
    public function request(string $method, string $uri, array $options = []): array;
}
