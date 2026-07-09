<?php

declare(strict_types=1);

use LetMeSendEmail\Client;
use LetMeSendEmail\Configuration;
use LetMeSendEmail\Http\TransportInterface;
use LetMeSendEmail\Resources\ContactCategoriesResource;
use LetMeSendEmail\Resources\ContactsResource;
use LetMeSendEmail\Resources\DomainsResource;
use LetMeSendEmail\Resources\EmailsResource;
use LetMeSendEmail\Resources\EmailTopicsResource;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->transport = Mockery::mock(TransportInterface::class);
});

afterEach(function () {
    Mockery::close();
});

function captureUri(TransportInterface $transport, string $method, string $path): void
{
    $transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $m, string $uri) => $m === $method && str_contains($uri, $path))
        ->andReturn(['status' => 200, 'headers' => [], 'body' => []]);
}

class UriCapturer
{
    public array $uris = [];
}

test('default base URL is used for Emails requests', function () {
    $config = new Configuration(apiKey: 'test_key');
    $client = new Client($config, $this->transport);

    $capturer = new UriCapturer();
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri) use ($capturer) {
            $capturer->uris[] = $uri;
            return true;
        })
        ->andReturn(['status' => 200, 'headers' => [], 'body' => []]);

    (new EmailsResource($client))->list();
    expect($capturer->uris[0])->toStartWith('https://letmesend.email/api/v1/emails');
});

test('default base URL is used for Domains requests', function () {
    $config = new Configuration(apiKey: 'test_key');
    $client = new Client($config, $this->transport);

    $capturer = new UriCapturer();
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri) use ($capturer) {
            $capturer->uris[] = $uri;
            return true;
        })
        ->andReturn(['status' => 200, 'headers' => [], 'body' => []]);

    (new DomainsResource($client))->list();
    expect($capturer->uris[0])->toStartWith('https://letmesend.email/api/v1/domains');
});

test('default base URL is used for Contacts requests', function () {
    $config = new Configuration(apiKey: 'test_key');
    $client = new Client($config, $this->transport);

    $capturer = new UriCapturer();
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri) use ($capturer) {
            $capturer->uris[] = $uri;
            return true;
        })
        ->andReturn(['status' => 200, 'headers' => [], 'body' => []]);

    (new ContactsResource($client))->list();
    expect($capturer->uris[0])->toStartWith('https://letmesend.email/api/v1/contacts');
});

test('default base URL is used for Contact Categories requests', function () {
    $config = new Configuration(apiKey: 'test_key');
    $client = new Client($config, $this->transport);

    $capturer = new UriCapturer();
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri) use ($capturer) {
            $capturer->uris[] = $uri;
            return true;
        })
        ->andReturn(['status' => 200, 'headers' => [], 'body' => []]);

    (new ContactCategoriesResource($client))->list();
    expect($capturer->uris[0])->toStartWith('https://letmesend.email/api/v1/contact-categories');
});

test('default base URL is used for Email Topics requests', function () {
    $config = new Configuration(apiKey: 'test_key');
    $client = new Client($config, $this->transport);

    $capturer = new UriCapturer();
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri) use ($capturer) {
            $capturer->uris[] = $uri;
            return true;
        })
        ->andReturn(['status' => 200, 'headers' => [], 'body' => []]);

    (new EmailTopicsResource($client))->list();
    expect($capturer->uris[0])->toStartWith('https://letmesend.email/api/v1/email-topics');
});

test('custom base URL is used for Emails requests', function () {
    $config = new Configuration(apiKey: 'test_key', baseUrl: 'https://custom.example.com/v2');
    $client = new Client($config, $this->transport);

    $capturer = new UriCapturer();
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri) use ($capturer) {
            $capturer->uris[] = $uri;
            return true;
        })
        ->andReturn(['status' => 200, 'headers' => [], 'body' => []]);

    (new EmailsResource($client))->list();
    expect($capturer->uris[0])->toStartWith('https://custom.example.com/v2/emails');
});

test('custom base URL is used for Domains requests', function () {
    $config = new Configuration(apiKey: 'test_key', baseUrl: 'https://custom.example.com/v2');
    $client = new Client($config, $this->transport);

    $capturer = new UriCapturer();
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri) use ($capturer) {
            $capturer->uris[] = $uri;
            return true;
        })
        ->andReturn(['status' => 200, 'headers' => [], 'body' => []]);

    (new DomainsResource($client))->list();
    expect($capturer->uris[0])->toStartWith('https://custom.example.com/v2/domains');
});

test('custom base URL is used for Contacts requests', function () {
    $config = new Configuration(apiKey: 'test_key', baseUrl: 'https://custom.example.com/v2');
    $client = new Client($config, $this->transport);

    $capturer = new UriCapturer();
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri) use ($capturer) {
            $capturer->uris[] = $uri;
            return true;
        })
        ->andReturn(['status' => 200, 'headers' => [], 'body' => []]);

    (new ContactsResource($client))->list();
    expect($capturer->uris[0])->toStartWith('https://custom.example.com/v2/contacts');
});

test('custom base URL is used for Contact Categories requests', function () {
    $config = new Configuration(apiKey: 'test_key', baseUrl: 'https://custom.example.com/v2');
    $client = new Client($config, $this->transport);

    $capturer = new UriCapturer();
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri) use ($capturer) {
            $capturer->uris[] = $uri;
            return true;
        })
        ->andReturn(['status' => 200, 'headers' => [], 'body' => []]);

    (new ContactCategoriesResource($client))->list();
    expect($capturer->uris[0])->toStartWith('https://custom.example.com/v2/contact-categories');
});

test('custom base URL is used for Email Topics requests', function () {
    $config = new Configuration(apiKey: 'test_key', baseUrl: 'https://custom.example.com/v2');
    $client = new Client($config, $this->transport);

    $capturer = new UriCapturer();
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri) use ($capturer) {
            $capturer->uris[] = $uri;
            return true;
        })
        ->andReturn(['status' => 200, 'headers' => [], 'body' => []]);

    (new EmailTopicsResource($client))->list();
    expect($capturer->uris[0])->toStartWith('https://custom.example.com/v2/email-topics');
});
