<?php

declare(strict_types=1);

namespace LetMeSendEmail;

use LetMeSendEmail\Exceptions\ApiError;
use LetMeSendEmail\Exceptions\AuthenticationError;
use LetMeSendEmail\Exceptions\AuthorizationError;
use LetMeSendEmail\Exceptions\ConflictError;
use LetMeSendEmail\Exceptions\NotFoundError;
use LetMeSendEmail\Exceptions\RateLimitError;
use LetMeSendEmail\Exceptions\ValidationError;
use LetMeSendEmail\Http\TransportInterface;

final class Client
{
    private Configuration $config;
    private TransportInterface $transport;

    public function __construct(Configuration $config, TransportInterface $transport)
    {
        $this->config = $config;
        $this->transport = $transport;
    }

    public function getConfiguration(): Configuration
    {
        return $this->config;
    }

    public function request(string $method, string $path, ?array $body = null, array $headers = []): array
    {
        $defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->config->getApiKey(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => $this->buildUserAgent(),
        ];

        $options = [
            'headers' => array_merge($defaultHeaders, $headers),
            'timeout' => $this->config->getTimeout(),
        ];

        if ($body !== null) {
            $options['body'] = $body;
        }

        $uri = $this->config->getBaseUrl() . '/' . ltrim($path, '/');

        $response = $this->transport->request($method, $uri, $options);

        if ($response['status'] >= 400) {
            $this->handleErrorResponse($response);
        }

        return $response['body'];
    }

    private function buildUserAgent(): string
    {
        return 'letmesendemail-php/' . LetMeSendEmail::VERSION;
    }

    private function handleErrorResponse(array $response): never
    {
        $status = (int) $response['status'];
        $body = $response['body'] ?? [];
        $headers = $response['headers'] ?? [];

        $message = $body['message'] ?? 'Unknown error.';
        $apiCode = $body['name'] ?? null;
        $validationErrors = $body['errors'] ?? [];
        $rawBody = json_encode($body);

        $requestId = null;
        if (isset($headers['X-Request-Id'][0])) {
            $requestId = $headers['X-Request-Id'][0];
        }

        $exception = match (true) {
            $status === 400 => ValidationError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status === 401 => AuthenticationError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status === 403 => AuthorizationError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status === 404 => NotFoundError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status === 409 => ConflictError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status === 413 => ValidationError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status === 422 => ValidationError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status === 429 => RateLimitError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            $status >= 500 && $status < 600 => ApiError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
            default => ApiError::fromResponse(
                message: $message,
                httpStatus: $status,
                apiCode: $apiCode,
                validationErrors: $validationErrors,
                headers: $headers,
                requestId: $requestId,
                rawBody: $rawBody,
            ),
        };

        throw $exception;
    }
}
