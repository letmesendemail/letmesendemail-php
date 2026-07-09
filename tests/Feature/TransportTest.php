<?php

declare(strict_types=1);

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use LetMeSendEmail\Configuration;
use LetMeSendEmail\Http\GuzzleTransport;
use Tests\TestCase;

uses(TestCase::class);

test('guzzle transport parses success response', function () {
    $mock = new MockHandler([
        new Response(200, ['Content-Type' => 'application/json'], json_encode(['id' => '123', 'status' => 'sent'])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $guzzle = new GuzzleClient(['handler' => $handlerStack]);
    $transport = new GuzzleTransport($guzzle);

    $result = $transport->request('GET', 'https://api.test/emails');

    expect($result['status'])->toBe(200);
    expect($result['body'])->toBe(['id' => '123', 'status' => 'sent']);
});

test('guzzle transport parses error response without throwing', function () {
    $mock = new MockHandler([
        new Response(422, ['Content-Type' => 'application/json'], json_encode([
            'message' => 'Validation failed.',
            'errors' => ['from' => ['Required.']],
        ])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $guzzle = new GuzzleClient(['handler' => $handlerStack]);
    $transport = new GuzzleTransport($guzzle);

    $result = $transport->request('POST', 'https://api.test/emails', ['body' => ['subject' => 'Hi']]);

    expect($result['status'])->toBe(422);
    expect($result['body']['message'])->toBe('Validation failed.');
});

test('guzzle transport returns response headers', function () {
    $mock = new MockHandler([
        new Response(200, ['X-Request-Id' => ['req_abc123']], json_encode(['id' => '1'])),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $guzzle = new GuzzleClient(['handler' => $handlerStack]);
    $transport = new GuzzleTransport($guzzle);

    $result = $transport->request('GET', 'https://api.test/emails/1');

    expect($result['headers']['X-Request-Id'][0])->toBe('req_abc123');
});

test('configuration base url override', function () {
    $config = new Configuration(
        apiKey: 'test_key',
        baseUrl: 'https://custom.api.letmesend.email/v2',
    );

    expect($config->getBaseUrl())->toBe('https://custom.api.letmesend.email/v2');
});

test('configuration timeout override', function () {
    $config = new Configuration(
        apiKey: 'test_key',
        timeout: 60,
    );

    expect($config->getTimeout())->toBe(60);
});

test('configuration default values', function () {
    $config = new Configuration(apiKey: 'test_key');

    expect($config->getBaseUrl())->toBe('https://letmesend.email/api/v1');
    expect($config->getTimeout())->toBe(30);
});
