<?php

declare(strict_types=1);

use LetMeSendEmail\Client;
use LetMeSendEmail\Configuration;
use LetMeSendEmail\Exceptions\ApiError;
use LetMeSendEmail\Exceptions\AuthenticationError;
use LetMeSendEmail\Exceptions\NetworkError;
use LetMeSendEmail\Exceptions\NotFoundError;
use LetMeSendEmail\Exceptions\RateLimitError;
use LetMeSendEmail\Exceptions\TimeoutError;
use LetMeSendEmail\Exceptions\ValidationError;
use LetMeSendEmail\Http\SleeperInterface;
use LetMeSendEmail\Http\TransportInterface;
use Tests\TestCase;

uses(TestCase::class);

class TestSleeper implements SleeperInterface
{
    public array $delays = [];

    public function sleep(int $milliseconds): void
    {
        $this->delays[] = $milliseconds;
    }
}

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
            return (bool) preg_match('/^letmesendemail-php\/.+/', $options['headers']['User-Agent']);
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

// --- Retry tests ---

test('retries on NetworkError with GET', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 2);
    $sleeper = new TestSleeper();
    $client = new Client($config, $this->transport, $sleeper);

    $this->transport
        ->shouldReceive('request')
        ->times(3)
        ->andThrow(NetworkError::fromRequest('Connection refused.'));

    expect(fn () => $client->request('GET', '/emails'))
        ->toThrow(NetworkError::class);

    expect($sleeper->delays)->toHaveCount(2);
});

test('retries on RateLimitError with Retry-After', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 1);
    $sleeper = new TestSleeper();
    $client = new Client($config, $this->transport, $sleeper);

    $this->transport
        ->shouldReceive('request')
        ->times(2)
        ->andReturn(
            [
                'status' => 429,
                'headers' => ['Retry-After' => ['5']],
                'body' => ['name' => 'rate_limited', 'message' => 'Too fast'],
            ],
            [
                'status' => 200,
                'headers' => [],
                'body' => ['id' => '123'],
            ],
        );

    $result = $client->request('GET', '/emails');

    expect($result)->toBe(['id' => '123']);
    expect($sleeper->delays)->toHaveCount(1);
    expect($sleeper->delays[0])->toBe(5000);
});

test('retries on 500 for idempotent GET', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 1);
    $sleeper = new TestSleeper();
    $client = new Client($config, $this->transport, $sleeper);

    $this->transport
        ->shouldReceive('request')
        ->times(2)
        ->andReturn(
            [
                'status' => 500,
                'headers' => [],
                'body' => ['name' => 'server_error', 'message' => 'Internal error'],
            ],
            [
                'status' => 200,
                'headers' => [],
                'body' => ['id' => '456'],
            ],
        );

    $result = $client->request('GET', '/emails');

    expect($result)->toBe(['id' => '456']);
});

test('does not retry non-idempotent POST without Idempotency-Key', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 1);
    $client = new Client($config, $this->transport, new TestSleeper());

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => 500,
            'headers' => [],
            'body' => ['name' => 'server_error', 'message' => 'Internal error'],
        ]);

    expect(fn () => $client->request('POST', '/emails', body: ['key' => 'val']))
        ->toThrow(ApiError::class);
});

test('retries idempotent POST with Idempotency-Key', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 1);
    $sleeper = new TestSleeper();
    $client = new Client($config, $this->transport, $sleeper);

    $this->transport
        ->shouldReceive('request')
        ->times(2)
        ->andReturn(
            [
                'status' => 500,
                'headers' => [],
                'body' => ['name' => 'server_error', 'message' => 'Internal error'],
            ],
            [
                'status' => 200,
                'headers' => [],
                'body' => ['id' => '789'],
            ],
        );

    $result = $client->request('POST', '/emails', body: ['key' => 'val'], headers: ['Idempotency-Key' => 'idem123']);

    expect($result)->toBe(['id' => '789']);
});

test('exhausts retries then throws', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 2);
    $sleeper = new TestSleeper();
    $client = new Client($config, $this->transport, $sleeper);

    $this->transport
        ->shouldReceive('request')
        ->times(3)
        ->andReturn([
            'status' => 500,
            'headers' => [],
            'body' => ['name' => 'server_error', 'message' => 'Server error'],
        ]);

    expect(fn () => $client->request('GET', '/emails'))
        ->toThrow(ApiError::class);

    expect($sleeper->delays)->toHaveCount(2);
});

test('detects Idempotency-Key case-insensitively', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 1);
    $sleeper = new TestSleeper();
    $client = new Client($config, $this->transport, $sleeper);

    $this->transport
        ->shouldReceive('request')
        ->times(2)
        ->andReturn(
            [
                'status' => 500,
                'headers' => [],
                'body' => ['name' => 'server_error', 'message' => 'Internal error'],
            ],
            [
                'status' => 200,
                'headers' => [],
                'body' => ['id' => 'abc'],
            ],
        );

    $result = $client->request('POST', '/emails', body: ['key' => 'val'], headers: ['idempotency-key' => 'idem123']);

    expect($result)->toBe(['id' => 'abc']);
});

test('malformed 2xx response throws ApiError', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => 200,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '',
        ]);

    try {
        $this->client->request('GET', '/emails');
    } catch (ApiError $e) {
        expect($e->getHttpStatus())->toBe(200);
        expect($e->getMessage())->toBe('Malformed response body');
        expect($e->getHeaders())->toHaveKey('Content-Type');

        return;
    }

    $this->fail('Expected ApiError was not thrown.');
});

test('retry delays respect Retry-After delta-seconds', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 1);
    $sleeper = new TestSleeper();
    $client = new Client($config, $this->transport, $sleeper);

    $this->transport
        ->shouldReceive('request')
        ->times(2)
        ->andReturn(
            [
                'status' => 429,
                'headers' => ['Retry-After' => ['3']],
                'body' => ['name' => 'rate_limited', 'message' => 'Too fast'],
            ],
            [
                'status' => 200,
                'headers' => [],
                'body' => ['id' => 'xyz'],
            ],
        );

    $result = $client->request('GET', '/emails');

    expect($result)->toBe(['id' => 'xyz']);
    expect($sleeper->delays)->toHaveCount(1);
    expect($sleeper->delays[0])->toBe(3000);
});

test('retry delays respect Retry-After HTTP-date', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 1);
    $sleeper = new TestSleeper();
    $client = new Client($config, $this->transport, $sleeper);

    $futureTimestamp = time() + 60;
    $futureDate = gmdate('D, d M Y H:i:s \G\M\T', $futureTimestamp);

    $this->transport
        ->shouldReceive('request')
        ->times(2)
        ->andReturn(
            [
                'status' => 429,
                'headers' => ['Retry-After' => [$futureDate]],
                'body' => ['name' => 'rate_limited', 'message' => 'Too fast'],
            ],
            [
                'status' => 200,
                'headers' => [],
                'body' => ['id' => 'xyz'],
            ],
        );

    $result = $client->request('GET', '/emails');

    expect($sleeper->delays)->toHaveCount(1);
    expect($sleeper->delays[0])->toBeGreaterThanOrEqual(58000);
    expect($sleeper->delays[0])->toBeLessThanOrEqual(60000);
});

test('throws RateLimitError on 429 with missing Retry-After', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 1);
    $sleeper = new TestSleeper();
    $client = new Client($config, $this->transport, $sleeper);

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => 429,
            'headers' => [],
            'body' => ['name' => 'rate_limited', 'message' => 'Too fast'],
        ]);

    expect(fn () => $client->request('GET', '/emails'))
        ->toThrow(RateLimitError::class);

    expect($sleeper->delays)->toHaveCount(0);
});

test('throws RateLimitError on 429 with invalid Retry-After zero', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 1);
    $sleeper = new TestSleeper();
    $client = new Client($config, $this->transport, $sleeper);

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => 429,
            'headers' => ['Retry-After' => ['0']],
            'body' => ['name' => 'rate_limited', 'message' => 'Too fast'],
        ]);

    expect(fn () => $client->request('GET', '/emails'))
        ->toThrow(RateLimitError::class);

    expect($sleeper->delays)->toHaveCount(0);
});

test('throws RateLimitError on 429 with excessive Retry-After beyond max', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 1);
    $sleeper = new TestSleeper();
    $client = new Client($config, $this->transport, $sleeper);

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => 429,
            'headers' => ['Retry-After' => ['301']],
            'body' => ['name' => 'rate_limited', 'message' => 'Too fast'],
        ]);

    expect(fn () => $client->request('GET', '/emails'))
        ->toThrow(RateLimitError::class);

    expect($sleeper->delays)->toHaveCount(0);
});

// --- Additional retry backoff tests ---

test('first retry delay is within 75-125% jitter range', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 3);
    $sleeper = new TestSleeper();
    $client = new Client($config, $this->transport, $sleeper);

    $this->transport
        ->shouldReceive('request')
        ->times(4)
        ->andThrow(NetworkError::fromRequest('Connection refused.'));

    expect(fn () => $client->request('GET', '/emails'))
        ->toThrow(NetworkError::class);

    expect($sleeper->delays)->toHaveCount(3);

    foreach ($sleeper->delays as $index => $delay) {
        $attempt = $index + 1;
        $baseMs = 100 * (2 ** ($attempt - 1));
        $minExpected = (int) ($baseMs * 0.75);
        $maxExpected = (int) ($baseMs * 1.25);

        expect($delay)->toBeGreaterThanOrEqual($minExpected);
        expect($delay)->toBeLessThanOrEqual($maxExpected);
    }
});

test('retry delay is capped at 300 seconds', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 1);
    $sleeper = new TestSleeper();
    $client = new Client($config, $this->transport, $sleeper);

    $this->transport
        ->shouldReceive('request')
        ->times(2)
        ->andThrow(NetworkError::fromRequest('Connection refused.'));

    expect(fn () => $client->request('GET', '/emails'))
        ->toThrow(NetworkError::class);

    expect($sleeper->delays)->toHaveCount(1);
    expect($sleeper->delays[0])->toBeLessThanOrEqual(300000);
});

test('sleeper is injected through LetMeSendEmail facade', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 1);
    $sleeper = new TestSleeper();

    $letMeSendEmail = new \LetMeSendEmail\LetMeSendEmail(
        configuration: $config,
        transport: $this->transport,
        sleeper: $sleeper,
    );

    $this->transport
        ->shouldReceive('request')
        ->times(2)
        ->andThrow(\LetMeSendEmail\Exceptions\NetworkError::fromRequest('Connection refused.'));

    expect(fn () => $letMeSendEmail->emails()->list())
        ->toThrow(\LetMeSendEmail\Exceptions\NetworkError::class);

    expect($sleeper->delays)->toHaveCount(1);
});

test('large attempt number is clamped to prevent overflow', function () {
    $config = new Configuration(apiKey: 'lms_test', retries: 30);
    $sleeper = new TestSleeper();
    $client = new Client($config, $this->transport, $sleeper);

    $this->transport
        ->shouldReceive('request')
        ->times(31)
        ->andThrow(NetworkError::fromRequest('Connection refused.'));

    expect(fn () => $client->request('GET', '/emails'))
        ->toThrow(NetworkError::class);

    expect($sleeper->delays)->toHaveCount(30);

    // All delays should be capped at 300 seconds
    foreach ($sleeper->delays as $delay) {
        expect($delay)->toBeLessThanOrEqual(300000);
    }
});
