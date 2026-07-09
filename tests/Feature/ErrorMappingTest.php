<?php

declare(strict_types=1);

use LetMeSendEmail\Client;
use LetMeSendEmail\Configuration;
use LetMeSendEmail\Exceptions\ApiError;
use LetMeSendEmail\Exceptions\AuthenticationError;
use LetMeSendEmail\Exceptions\AuthorizationError;
use LetMeSendEmail\Exceptions\ConflictError;
use LetMeSendEmail\Exceptions\NotFoundError;
use LetMeSendEmail\Exceptions\RateLimitError;
use LetMeSendEmail\Exceptions\ValidationError;
use LetMeSendEmail\Http\TransportInterface;
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

test('400 maps to validation error', function () {
    $this->transport->shouldReceive('request')->andReturn(['status' => 400, 'headers' => [], 'body' => ['message' => 'Bad request.', 'name' => 'bad_request']]);
    expect(fn () => $this->client->request('POST', '/emails'))->toThrow(ValidationError::class);
});

test('401 maps to authentication error using domain-unverified fixture', function () {
    $fixture = loadFixture('error-responses/domain-unverified.json');
    $this->transport->shouldReceive('request')->andReturn(['status' => 401, 'headers' => [], 'body' => $fixture['response']]);
    expect(fn () => $this->client->request('GET', '/emails'))->toThrow(AuthenticationError::class);
});

test('403 maps to authorization error', function () {
    $this->transport->shouldReceive('request')->andReturn(['status' => 403, 'headers' => [], 'body' => ['message' => 'Forbidden.', 'name' => 'forbidden']]);
    expect(fn () => $this->client->request('GET', '/emails'))->toThrow(AuthorizationError::class);
});

test('404 maps to not found error using domain-not-found fixture', function () {
    $fixture = loadFixture('error-responses/domain-not-found.json');
    $this->transport->shouldReceive('request')->andReturn(['status' => 404, 'headers' => [], 'body' => $fixture['response']]);
    expect(fn () => $this->client->request('GET', '/emails'))->toThrow(NotFoundError::class);
});

test('409 maps to conflict error', function () {
    $this->transport->shouldReceive('request')->andReturn(['status' => 409, 'headers' => [], 'body' => ['message' => 'Conflict.', 'name' => 'conflict']]);
    expect(fn () => $this->client->request('POST', '/emails'))->toThrow(ConflictError::class);
});

test('413 maps to validation error using email-size-exceeded fixture', function () {
    $fixture = loadFixture('error-responses/email-size-exceeded.json');
    $this->transport->shouldReceive('request')->andReturn(['status' => 413, 'headers' => [], 'body' => $fixture['response']]);
    expect(fn () => $this->client->request('POST', '/emails'))->toThrow(ValidationError::class);
});

test('422 maps to validation error', function () {
    $this->transport->shouldReceive('request')->andReturn(['status' => 422, 'headers' => [], 'body' => ['message' => 'Validation failed.', 'errors' => ['email' => ['Invalid email.']]]]);
    expect(fn () => $this->client->request('POST', '/emails'))->toThrow(ValidationError::class);
});

test('429 maps to rate limit error using daily-quote-exceed fixture', function () {
    $fixture = loadFixture('error-responses/daily-quote-exceed.json');
    $this->transport->shouldReceive('request')->andReturn(['status' => 429, 'headers' => [], 'body' => $fixture['response']]);
    expect(fn () => $this->client->request('POST', '/emails'))->toThrow(RateLimitError::class);
});

test('429 maps to rate limit error using monthly-quote-exceed fixture', function () {
    $fixture = loadFixture('error-responses/monthly-quote-exceed.json');
    $this->transport->shouldReceive('request')->andReturn(['status' => 429, 'headers' => [], 'body' => $fixture['response']]);
    expect(fn () => $this->client->request('POST', '/emails'))->toThrow(RateLimitError::class);
});

test('500 maps to api error', function () {
    $this->transport->shouldReceive('request')->andReturn(['status' => 500, 'headers' => [], 'body' => ['message' => 'Server error.']]);
    expect(fn () => $this->client->request('GET', '/emails'))->toThrow(ApiError::class);
});

test('503 maps to api error', function () {
    $this->transport->shouldReceive('request')->andReturn(['status' => 503, 'headers' => [], 'body' => ['message' => 'Service unavailable.']]);
    expect(fn () => $this->client->request('GET', '/emails'))->toThrow(ApiError::class);
});
