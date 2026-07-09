<?php

declare(strict_types=1);

use LetMeSendEmail\Client;
use LetMeSendEmail\Configuration;
use LetMeSendEmail\Exceptions\AuthenticationError;
use LetMeSendEmail\Exceptions\NetworkError;
use LetMeSendEmail\Exceptions\NotFoundError;
use LetMeSendEmail\Exceptions\RateLimitError;
use LetMeSendEmail\Exceptions\TimeoutError;
use LetMeSendEmail\Exceptions\ValidationError;
use LetMeSendEmail\Http\TransportInterface;
use LetMeSendEmail\LetMeSendEmail;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->config = new Configuration(apiKey: 'lms_live_test_key');
    $this->transport = Mockery::mock(TransportInterface::class);
    $this->client = new Client($this->config, $this->transport);
});

afterEach(function () {
    Mockery::close();
});

test('configuration is accessible from client', function () {
    expect($this->client->getConfiguration())->toBe($this->config);
});

test('request sends authorization header', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            return $method === 'GET'
                && str_contains($uri, (string) $this->config->getBaseUrl())
                && str_contains($options['headers']['Authorization'], 'Bearer lms_live_test_key');
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => [],
        ]);

    $this->client->request('GET', '/emails');
});

test('request sends content-type and accept headers', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            return $options['headers']['Content-Type'] === 'application/json'
                && $options['headers']['Accept'] === 'application/json';
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => [],
        ]);

    $this->client->request('GET', '/emails');
});

test('request sends user-agent header with package slug and version', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            return $options['headers']['User-Agent'] === 'letmesendemail-php/' . LetMeSendEmail::VERSION;
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => [],
        ]);

    $this->client->request('GET', '/emails');
});

test('request uses config timeout', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            return $options['timeout'] === $this->config->getTimeout();
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => [],
        ]);

    $this->client->request('GET', '/emails');
});

test('request constructs full url from base url and path', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri) {
            return $uri === 'https://letmesend.email/api/v1/emails';
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => [],
        ]);

    $this->client->request('GET', '/emails');
});

test('request sends json body', function () {
    $body = ['from' => 'test@example.com', 'subject' => 'Hello'];

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) use ($body) {
            return $options['body'] === $body;
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => [],
        ]);

    $this->client->request('POST', '/emails', body: $body);
});

test('request passes extra headers', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            return $options['headers']['Idempotency-Key'] === 'my-key';
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => [],
        ]);

    $this->client->request('POST', '/emails', headers: ['Idempotency-Key' => 'my-key']);
});

test('request returns response body on success', function () {
    $responseBody = ['id' => 'email_123', 'status' => 'sent'];

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => $responseBody,
        ]);

    $result = $this->client->request('GET', '/emails/email_123');

    expect($result)->toBe($responseBody);
});

test('request throws authentication error on 401', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => 401,
            'headers' => [],
            'body' => [
                'status' => 'error',
                'name' => 'domain_unverified',
                'message' => 'Domain unverified.',
                'errors' => [],
                'code' => 401,
            ],
        ]);

    $this->client->request('GET', '/emails');
})->throws(AuthenticationError::class);

test('request throws validation error on 422', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => 422,
            'headers' => [],
            'body' => [
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => ['from' => ['The from field is required.']],
                'code' => 422,
            ],
        ]);

    $this->client->request('POST', '/emails');
})->throws(ValidationError::class);

test('request throws not found error on 404', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => 404,
            'headers' => [],
            'body' => [
                'status' => 'error',
                'name' => 'domain_not_found',
                'message' => 'Domain not found.',
                'errors' => [],
                'code' => 404,
            ],
        ]);

    $this->client->request('GET', '/emails/unknown');
})->throws(NotFoundError::class);

test('request throws rate limit error on 429', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => 429,
            'headers' => ['Retry-After' => ['120']],
            'body' => [
                'status' => 'error',
                'name' => 'daily_quota_exceeded',
                'message' => 'You have exceeded your daily quota limit.',
                'errors' => [],
                'code' => 429,
            ],
        ]);

    $this->client->request('GET', '/emails');
})->throws(RateLimitError::class);

test('rate limit error exposes retry after', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => 429,
            'headers' => ['Retry-After' => ['120']],
            'body' => [
                'status' => 'error',
                'name' => 'daily_quota_exceeded',
                'message' => 'You have exceeded your daily quota limit.',
                'errors' => [],
                'code' => 429,
            ],
        ]);

    try {
        $this->client->request('GET', '/emails');
    } catch (RateLimitError $e) {
        expect($e->getRetryAfter())->toBe(120);
        expect($e->getHttpStatus())->toBe(429);
        expect($e->getApiCode())->toBe('daily_quota_exceeded');
    }
});

test('exception exposes api code and validation errors', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => 422,
            'headers' => [],
            'body' => [
                'status' => 'error',
                'name' => 'validation_error',
                'message' => 'The given data was invalid.',
                'errors' => ['from' => ['The from field is required.']],
                'code' => 422,
            ],
        ]);

    try {
        $this->client->request('POST', '/emails');
    } catch (ValidationError $e) {
        expect($e->getApiCode())->toBe('validation_error');
        expect($e->getValidationErrors())->toBe(['from' => ['The from field is required.']]);
        expect($e->getHttpStatus())->toBe(422);
    }
});

test('transport network error maps to NetworkError', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andThrow(NetworkError::fromRequest('Unable to connect.'));

    expect(fn () => $this->client->request('GET', '/emails'))
        ->toThrow(NetworkError::class);
});

test('transport timeout maps to TimeoutError', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andThrow(TimeoutError::fromRequest('Request timed out.'));

    expect(fn () => $this->client->request('GET', '/emails'))
        ->toThrow(TimeoutError::class);
});
