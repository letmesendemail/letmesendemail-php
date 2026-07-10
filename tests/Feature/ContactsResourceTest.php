<?php

declare(strict_types=1);

use LetMeSendEmail\Client;
use LetMeSendEmail\Configuration;
use LetMeSendEmail\Http\TransportInterface;
use LetMeSendEmail\Resources\ContactsResource;
use LetMeSendEmail\Responses\ContactListResponse;
use LetMeSendEmail\Responses\ContactResponse;
use LetMeSendEmail\Responses\ContactUpdateResponse;
use LetMeSendEmail\Responses\StatusResponse;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->config = new Configuration(apiKey: 'lms_live_test_key');
    $this->transport = Mockery::mock(TransportInterface::class);
    $this->client = new Client($this->config, $this->transport);
    $this->contacts = new ContactsResource($this->client);
});

afterEach(function () {
    Mockery::close();
});

test('create sends POST to /contacts and returns contact from fixture', function () {
    $fixture = loadFixture('contacts/store.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options) => $method === 'POST' && str_contains($uri, '/contacts') && $options['body']['email'] === 'john@example.com' && $options['body']['first_name'] === 'John')
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->contacts->create(
        email: 'john@example.com',
        firstName: 'John',
        lastName: 'Doe',
        phone: '11231231234',
        isGloballyUnsubscribed: false,
        categories: ['01kvtsch6f6e7hz543mjyjnqsp', '01kvtsch6f6e7hz543mjyjnqsq'],
        emailTopics: ['01kvtsch6gjgtx89z84jn8zwqg', '01kvtsch6hddk02bdv5ez2fq7y'],
    );

    expect($response)->toBeInstanceOf(ContactResponse::class);
    expect($response->getId())->toBe($fixture['response']['data']['id']);
    expect($response->getEmail())->toBe('john@example.com');
    expect($response->getFirstName())->toBe('John');
    expect($response->getLastName())->toBe('Doe');
    expect($response->getPhone())->toBe('11231231234');
    expect($response->isGloballyUnsubscribed())->toBeFalse();
    expect($response->getCategories())->toHaveCount(2);
});

test('create omits optional fields when not provided', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options) => !isset($options['body']['phone']) && !isset($options['body']['categories']) && !isset($options['body']['email_topics']) && !isset($options['body']['is_globally_unsubscribed']))
        ->andReturn(['status' => 200, 'headers' => [], 'body' => ['id' => 'c1', 'email' => 'test@example.com', 'first_name' => null, 'last_name' => null, 'phone' => null, 'is_globally_unsubscribed' => false, 'created_at' => '2026-01-01T00:00:00Z']]);

    $this->contacts->create(email: 'test@example.com');
});

test('list sends GET to /contacts and returns paginated response', function () {
    $fixture = loadFixture('contacts/list.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri) => $method === 'GET' && str_contains($uri, '/contacts'))
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->contacts->list();

    expect($response)->toBeInstanceOf(ContactListResponse::class);
    expect($response->items())->toHaveCount(20);
    expect($response->pagination()->hasMore())->toBeTrue();
    expect($response->pagination()->getTotal())->toBe(100);
});

test('get sends GET to /contacts/{id} and returns contact from fixture', function () {
    $fixture = loadFixture('contacts/show.json');
    $contactId = $fixture['response']['data']['id'];

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri) => $method === 'GET' && str_contains($uri, '/contacts/' . $contactId))
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->contacts->get($contactId);

    expect($response)->toBeInstanceOf(ContactResponse::class);
    expect($response->getId())->toBe($contactId);
    expect($response->getEmail())->toBe($fixture['response']['data']['email']);
    expect($response->getCategories())->toHaveCount(1);
});

test('update sends PUT to /contacts/{id} and returns response', function () {
    $fixture = loadFixture('contacts/update.json');
    $contactId = $fixture['response']['data']['id'];

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options) => $method === 'PUT' && str_contains($uri, '/contacts/' . $contactId) && $options['body']['first_name'] === 'John')
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->contacts->update(
        id: $contactId,
        firstName: 'John',
        lastName: 'Doe',
    );

    expect($response)->toBeInstanceOf(ContactUpdateResponse::class);
    expect($response->getId())->toBe($contactId);
});

test('delete sends DELETE to /contacts/{id} and returns status response from fixture', function () {
    $fixture = loadFixture('contacts/delete.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri) => $method === 'DELETE' && str_contains($uri, '/contacts/01kvtscham9bjdwftnxxa8at1k'))
        ->andReturn(['status' => $fixture['response']['status'], 'headers' => [], 'body' => $fixture['response']['data']]);

    $response = $this->contacts->delete('01kvtscham9bjdwftnxxa8at1k');

    expect($response)->toBeInstanceOf(StatusResponse::class);
    expect($response->getStatus())->toBe('success');
});
