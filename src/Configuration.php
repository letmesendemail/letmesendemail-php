<?php

declare(strict_types=1);

namespace LetMeSendEmail;

final class Configuration
{
    private const DEFAULT_BASE_URL = 'https://letmesend.email/api/v1';
    private const DEFAULT_TIMEOUT = 30;

    private string $apiKey;
    private string $baseUrl;
    private int $timeout;

    public function __construct(
        string $apiKey,
        ?string $baseUrl = null,
        ?int $timeout = null,
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl ?? self::DEFAULT_BASE_URL, '/');
        $this->timeout = $timeout ?? self::DEFAULT_TIMEOUT;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
