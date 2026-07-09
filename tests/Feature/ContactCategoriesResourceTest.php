<?php

declare(strict_types=1);

use LetMeSendEmail\Client;
use LetMeSendEmail\Configuration;
use LetMeSendEmail\Http\TransportInterface;
use LetMeSendEmail\Resources\ContactCategoriesResource;
use LetMeSendEmail\Responses\ContactCategoryListResponse;
use LetMeSendEmail\Responses\ContactCategoryResponse;
use LetMeSendEmail\Responses\StatusResponse;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->config = new Configuration(apiKey: 'lms_live_test_key');
    $this->transport = Mockery::mock(TransportInterface::class);
    $this->client = new Client($this->config, $this->transport);
    $this->categories = new ContactCategoriesResource($this->client);
});

afterEach(function () {
    Mockery::close();
});

test('create sends POST to /contact-categories and returns category from fixture', function () {
    $fixture = loadFixture('contact-categories/store.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options) => $method === 'POST' && str_contains($uri, '/contact-categories') && $options['body']['name'] === 'New Name')
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->categories->create(name: 'New Name');

    expect($response)->toBeInstanceOf(ContactCategoryResponse::class);
    expect($response->getId())->toBe($fixture['response']['data']['id']);
    expect($response->getName())->toBe('New Name');
    expect($response->getSlug())->toBe('new-name');
});

test('list sends GET to /contact-categories and returns paginated response from fixture', function () {
    $fixture = loadFixture('contact-categories/list.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri) => $method === 'GET' && str_contains($uri, '/contact-categories'))
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->categories->list();

    expect($response)->toBeInstanceOf(ContactCategoryListResponse::class);
    expect($response->items())->toHaveCount(2);
    expect($response->pagination()->hasMore())->toBeTrue();
    expect($response->pagination()->getTotal())->toBe(100);
    expect($response->items()[0]->getName())->toBe('quisquam temporibus ullam');
});

test('get sends GET to /contact-categories/{id} and returns category from fixture', function () {
    $fixture = loadFixture('contact-categories/show.json');
    $id = $fixture['response']['data']['id'];

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri) => $method === 'GET' && str_contains($uri, '/contact-categories/' . $id))
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->categories->get($id);

    expect($response)->toBeInstanceOf(ContactCategoryResponse::class);
    expect($response->getName())->toBe('Category Name');
});

test('update sends PUT to /contact-categories/{id} and returns updated category from fixture', function () {
    $fixture = loadFixture('contact-categories/update.json');
    $id = $fixture['response']['data']['id'];

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options) => $method === 'PUT' && str_contains($uri, '/contact-categories/' . $id) && $options['body']['name'] === 'New Name')
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->categories->update(id: $id, name: 'New Name', slug: 'new-name');

    expect($response)->toBeInstanceOf(ContactCategoryResponse::class);
    expect($response->getName())->toBe('New Name');
    expect($response->getSlug())->toBe('new-name');
});

test('delete sends DELETE to /contact-categories/{id} and returns status response from fixture', function () {
    $fixture = loadFixture('contact-categories/delete.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri) => $method === 'DELETE' && str_contains($uri, '/contact-categories/01kvtmr3evcs2brxp2vcztd102'))
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->categories->delete('01kvtmr3evcs2brxp2vcztd102');

    expect($response)->toBeInstanceOf(StatusResponse::class);
    expect($response->getStatus())->toBe('success');
});
