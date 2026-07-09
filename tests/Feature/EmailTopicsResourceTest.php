<?php

declare(strict_types=1);

use LetMeSendEmail\Client;
use LetMeSendEmail\Configuration;
use LetMeSendEmail\Http\TransportInterface;
use LetMeSendEmail\Resources\EmailTopicsResource;
use LetMeSendEmail\Responses\EmailTopicListResponse;
use LetMeSendEmail\Responses\EmailTopicResponse;
use LetMeSendEmail\Responses\StatusResponse;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->config = new Configuration(apiKey: 'lms_live_test_key');
    $this->transport = Mockery::mock(TransportInterface::class);
    $this->client = new Client($this->config, $this->transport);
    $this->topics = new EmailTopicsResource($this->client);
});

afterEach(function () {
    Mockery::close();
});

test('create sends POST to /email-topics and returns topic from fixture', function () {
    $fixture = loadFixture('email-topics/store.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options) => $method === 'POST' && str_contains($uri, '/email-topics') && $options['body']['name'] === 'Product Updates' && $options['body']['slug'] === 'product-updates')
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->topics->create(
        name: 'Product Updates',
        slug: 'product-updates',
        description: 'Emails for product updates',
        autoSubscribe: true,
        public: true,
    );

    expect($response)->toBeInstanceOf(EmailTopicResponse::class);
    expect($response->getId())->toBe($fixture['response']['data']['id']);
    expect($response->getName())->toBe('Product Updates');
    expect($response->getSlug())->toBe('product-updates');
    expect($response->getDescription())->toBe('Emails for product updates');
    expect($response->isAutoSubscribe())->toBeTrue();
    expect($response->isPublic())->toBeTrue();
});

test('create with optional domain id', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options) => $options['body']['domain']['id'] === '01kvtsgfavkx5609jd3j2t6jr1')
        ->andReturn(['status' => 200, 'headers' => [], 'body' => ['id' => 't1', 'name' => 'Test', 'slug' => 'test', 'description' => null, 'auto_subscribe' => false, 'public' => false, 'created_at' => '2026-01-01T00:00:00Z', 'domain' => ['id' => '01kvtsgfavkx5609jd3j2t6jr1', 'name' => 'blcmiller.com']]]);

    $response = $this->topics->create(name: 'Test', slug: 'test', domainId: '01kvtsgfavkx5609jd3j2t6jr1');

    expect($response)->toBeInstanceOf(EmailTopicResponse::class);
    expect($response->getDomain())->not->toBeNull();
    expect($response->getDomain()['id'])->toBe('01kvtsgfavkx5609jd3j2t6jr1');
});

test('list sends GET to /email-topics and returns paginated response from fixture', function () {
    $fixture = loadFixture('email-topics/list.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri) => $method === 'GET' && str_contains($uri, '/email-topics'))
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->topics->list();

    expect($response)->toBeInstanceOf(EmailTopicListResponse::class);
    expect($response->items())->toHaveCount(2);
    expect($response->pagination()->hasMore())->toBeTrue();
    expect($response->pagination()->getTotal())->toBe(4);
});

test('get sends GET to /email-topics/{id} and returns topic from fixture', function () {
    $fixture = loadFixture('email-topics/show.json');
    $id = $fixture['response']['data']['id'];

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri) => $method === 'GET' && str_contains($uri, '/email-topics/' . $id))
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->topics->get($id);

    expect($response)->toBeInstanceOf(EmailTopicResponse::class);
    expect($response->getId())->toBe($id);
    expect($response->getName())->toBe('Billing Notifications');
    expect($response->isAutoSubscribe())->toBeFalse();
});

test('update sends PUT to /email-topics/{id} and returns updated topic from fixture', function () {
    $fixture = loadFixture('email-topics/update.json');
    $id = $fixture['response']['data']['id'];

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options) => $method === 'PUT' && str_contains($uri, '/email-topics/' . $id) && $options['body']['name'] === 'New Name')
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->topics->update(id: $id, name: 'New Name', description: 'Updated description', public: true);

    expect($response)->toBeInstanceOf(EmailTopicResponse::class);
    expect($response->getName())->toBe('New Name');
    expect($response->getDescription())->toBe('Updated description');
    expect($response->isPublic())->toBeTrue();
    expect($response->getDomain())->not->toBeNull();
});

test('delete sends DELETE to /email-topics/{id} and returns status response from fixture', function () {
    $fixture = loadFixture('email-topics/delete.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri) => $method === 'DELETE' && str_contains($uri, '/email-topics/01kvtsgfdq4xw54vcvqw0ae68n'))
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->topics->delete('01kvtsgfdq4xw54vcvqw0ae68n');

    expect($response)->toBeInstanceOf(StatusResponse::class);
    expect($response->getStatus())->toBe('success');
    expect($response->getMessage())->toBe('Email category deleted');
});
