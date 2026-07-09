<?php

declare(strict_types=1);

use LetMeSendEmail\Client;
use LetMeSendEmail\Configuration;
use LetMeSendEmail\Http\TransportInterface;
use LetMeSendEmail\Resources\DomainsResource;
use LetMeSendEmail\Responses\DomainListResponse;
use LetMeSendEmail\Responses\DomainResponse;
use LetMeSendEmail\Responses\StatusResponse;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->config = new Configuration(apiKey: 'lms_live_test_key');
    $this->transport = Mockery::mock(TransportInterface::class);
    $this->client = new Client($this->config, $this->transport);
    $this->domains = new DomainsResource($this->client);
});

afterEach(function () {
    Mockery::close();
});

test('list sends GET to /domains and returns paginated response from fixture', function () {
    $fixture = loadFixture('domains/list.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri) => $method === 'GET' && str_contains($uri, '/domains'))
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->domains->list();

    expect($response)->toBeInstanceOf(DomainListResponse::class);
    expect($response->pagination()->hasMore())->toBe($fixture['response']['data']['pagination']['has_more']);
    expect($response->pagination()->getTotal())->toBe($fixture['response']['data']['pagination']['total']);
    expect($response->items())->toHaveCount(4);
    expect($response->items()[0]->getDomainName())->toBe('99xshanahan.com');
    expect($response->items()[0]->getStatus())->toBe('verified');
});

test('list with pagination parameters', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri) => str_contains($uri, 'per_page=10') && str_contains($uri, 'after=cursor_a'))
        ->andReturn(['status' => 200, 'headers' => [], 'body' => ['data' => [], 'pagination' => ['has_more' => false, 'per_page' => 10, 'fetched' => 0, 'total' => 0]]]);

    $this->domains->list(perPage: 10, after: 'cursor_a');
});

test('get sends GET to /domains/{id} and returns domain from fixture', function () {
    $fixture = loadFixture('domains/show.json');
    $domainId = $fixture['response']['data']['id'];

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri) => $method === 'GET' && str_contains($uri, '/domains/' . $domainId))
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->domains->get($domainId);

    expect($response)->toBeInstanceOf(DomainResponse::class);
    expect($response->getId())->toBe($domainId);
    expect($response->getDomainName())->toBe($fixture['response']['data']['domain_name']);
    expect($response->getStatus())->toBe($fixture['response']['data']['status']);
});

test('verify sends POST to /domains/verify with domain', function () {
    $fixture = loadFixture('domains/verify.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options) => $method === 'POST' && str_contains($uri, '/domains/verify') && $options['body']['domain'] === 'mpxlubowitz.com')
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->domains->verify('mpxlubowitz.com');

    expect($response)->toBeInstanceOf(StatusResponse::class);
    expect($response->getStatus())->toBe('verified');
});
