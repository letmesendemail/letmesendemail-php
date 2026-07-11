<?php

declare(strict_types=1);

use LetMeSendEmail\Client;
use LetMeSendEmail\Configuration;
use LetMeSendEmail\Http\TransportInterface;
use LetMeSendEmail\Requests\Attachment;
use LetMeSendEmail\Requests\TemplateVariable;
use LetMeSendEmail\Resources\EmailsResource;
use LetMeSendEmail\Responses\EmailAttachmentResponse;
use LetMeSendEmail\Responses\EmailListResponse;
use LetMeSendEmail\Responses\EmailResponse;
use LetMeSendEmail\Responses\RecipientResponse;
use LetMeSendEmail\Responses\VerifyEmailResponse;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->config = new Configuration(apiKey: 'lms_live_test_key');
    $this->transport = Mockery::mock(TransportInterface::class);
    $this->client = new Client($this->config, $this->transport);
    $this->emails = new EmailsResource($this->client);
});

afterEach(function () {
    Mockery::close();
});

test('send constructs valid request body from send fixture', function () {
    $fixture = loadFixture('emails/send.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) use ($fixture) {
            if ($method !== 'POST' || !str_contains($uri, '/emails')) {
                return false;
            }

            return $options['body']['from'] === $fixture['request']['body']['from']
                && $options['body']['to'] === $fixture['request']['body']['to']
                && $options['body']['subject'] === $fixture['request']['body']['subject']
                && $options['body']['html'] === $fixture['request']['body']['html'];
        })
        ->andReturn([
            'status' => $fixture['response']['status'],
            'headers' => [],
            'body' => $fixture['response']['data'],
        ]);

    $response = $this->emails->send(
        from: $fixture['request']['body']['from'],
        to: $fixture['request']['body']['to'],
        subject: $fixture['request']['body']['subject'],
        html: $fixture['request']['body']['html'],
    );

    expect($response)->toBeInstanceOf(EmailResponse::class);
    expect($response->getId())->toBe($fixture['response']['data']['id']);
    expect($response->getStatus())->toBe($fixture['response']['data']['status']);
    expect($response->getEmails())->toBe($fixture['response']['data']['emails']);
});

test('send with all optional fields', function () {
    $fixture = loadFixture('emails/send.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) use ($fixture) {
            return $options['body']['cc'] === $fixture['request']['body']['cc']
                && $options['body']['bcc'] === $fixture['request']['body']['bcc']
                && $options['body']['reply_to'] === $fixture['request']['body']['reply_to']
                && $options['body']['event_name'] === $fixture['request']['body']['event_name']
                && $options['body']['type'] === $fixture['request']['body']['type']
                && $options['body']['headers'] === $fixture['request']['body']['headers'];
        })
        ->andReturn([
            'status' => $fixture['response']['status'],
            'headers' => [],
            'body' => $fixture['response']['data'],
        ]);

    $response = $this->emails->send(
        from: $fixture['request']['body']['from'],
        to: $fixture['request']['body']['to'],
        subject: $fixture['request']['body']['subject'],
        html: $fixture['request']['body']['html'],
        type: $fixture['request']['body']['type'],
        eventName: $fixture['request']['body']['event_name'],
        replyTo: $fixture['request']['body']['reply_to'],
        cc: $fixture['request']['body']['cc'],
        bcc: $fixture['request']['body']['bcc'],
        headers: $fixture['request']['body']['headers'],
    );

    expect($response)->toBeInstanceOf(EmailResponse::class);
});

test('send with idempotency key header', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            return isset($options['headers']['Idempotency-Key'])
                && $options['headers']['Idempotency-Key'] === 'idem-key-123';
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => ['id' => 'email_123', 'status' => 'accepted', 'emails' => ['john@example.com'], 'restricted_emails' => []],
        ]);

    $response = $this->emails->send(
        from: 'Test <test@example.com>',
        to: ['john@example.com'],
        subject: 'Hello',
        html: '<p>Hi</p>',
        idempotencyKey: 'idem-key-123',
    );

    expect($response)->toBeInstanceOf(EmailResponse::class);
});

test('send with idempotency duplicate response exposes isDuplicate', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => ['id' => 'dup_email', 'status' => 'accepted', 'duplicate' => true, 'emails' => ['john@example.com'], 'restricted_emails' => []],
        ]);

    $response = $this->emails->send(
        from: 'Test <test@example.com>',
        to: ['john@example.com'],
        subject: 'Hello',
        html: '<p>Hi</p>',
        idempotencyKey: 'dup-key',
    );

    expect($response->isDuplicate())->toBeTrue();
});

test('non-duplicate response exposes isDuplicate false', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => ['id' => 'new_email', 'status' => 'accepted', 'emails' => ['john@example.com'], 'restricted_emails' => []],
        ]);

    $response = $this->emails->send(
        from: 'Test <test@example.com>',
        to: ['john@example.com'],
        subject: 'Hello',
        html: '<p>Hi</p>',
    );

    expect($response->isDuplicate())->toBeFalse();
});

test('send with template constructs valid request from fixture', function () {
    $fixture = loadFixture('emails/send-with-template.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) use ($fixture) {
            return $options['body']['template_id'] === $fixture['request']['body']['template_id']
                && $options['body']['template_variables'] === $fixture['request']['body']['template_variables']
                && $options['body']['from'] === $fixture['request']['body']['from'];
        })
        ->andReturn([
            'status' => $fixture['response']['status'],
            'headers' => [],
            'body' => $fixture['response']['data'],
        ]);

    $response = $this->emails->sendWithTemplate(
        from: $fixture['request']['body']['from'],
        to: $fixture['request']['body']['to'],
        templateId: $fixture['request']['body']['template_id'],
        templateVariables: $fixture['request']['body']['template_variables'],
    );

    expect($response)->toBeInstanceOf(EmailResponse::class);
    expect($response->getId())->toBe($fixture['response']['data']['id']);
    expect($response->getStatus())->toBe($fixture['response']['data']['status']);
});

test('verify constructs valid request from fixture', function () {
    $fixture = loadFixture('emails/verify.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) use ($fixture) {
            return $method === 'POST'
                && str_contains($uri, '/emails/verify')
                && $options['body']['email'] === $fixture['request']['body']['email'];
        })
        ->andReturn([
            'status' => $fixture['response']['status'],
            'headers' => [],
            'body' => $fixture['response']['data'],
        ]);

    $response = $this->emails->verify($fixture['request']['body']['email']);

    expect($response)->toBeInstanceOf(VerifyEmailResponse::class);
    expect($response->getEmail())->toBe($fixture['response']['data']['email']);
    expect($response->getScore())->toBe($fixture['response']['data']['score']);
    expect($response->getStatus())->toBe($fixture['response']['data']['status']);
    expect($response->isDomainExists())->toBe($fixture['response']['data']['domain_exists']);
    expect($response->isDisposable())->toBe($fixture['response']['data']['disposable']);
    expect($response->isRoleBased())->toBe($fixture['response']['data']['role_based']);
    expect($response->hasMailbox())->toBe($fixture['response']['data']['has_mailbox']);
});

test('list returns paginated response from fixture', function () {
    $fixture = loadFixture('emails/list.json');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            return $method === 'GET'
                && str_contains($uri, '/emails');
        })
        ->andReturn([
            'status' => $fixture['response']['status'],
            'headers' => [],
            'body' => $fixture['response']['data'],
        ]);

    $response = $this->emails->list();

    expect($response)->toBeInstanceOf(EmailListResponse::class);
    expect($response->pagination()->hasMore())->toBe($fixture['response']['data']['pagination']['has_more']);
    expect($response->pagination()->getPerPage())->toBe($fixture['response']['data']['pagination']['per_page']);
    expect($response->pagination()->getTotal())->toBe($fixture['response']['data']['pagination']['total']);
    expect($response->items())->toHaveCount(2);
});

test('list with pagination parameters', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri) {
            return str_contains($uri, 'per_page=10')
                && str_contains($uri, 'after=cursor_abc');
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => ['data' => [], 'pagination' => ['has_more' => false, 'per_page' => 10, 'fetched' => 0, 'total' => 0]],
        ]);

    $this->emails->list(perPage: 10, after: 'cursor_abc');
});

test('list with before cursor', function () {
    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri) {
            return str_contains($uri, 'before=cursor_xyz');
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => ['data' => [], 'pagination' => ['has_more' => false, 'per_page' => 10, 'fetched' => 0, 'total' => 0]],
        ]);

    $this->emails->list(before: 'cursor_xyz');
});

test('get returns email from fixture', function () {
    $fixture = loadFixture('emails/show.json');
    $emailId = $fixture['response']['data']['id'];

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri) use ($emailId) {
            return $method === 'GET'
                && str_contains($uri, '/emails/' . $emailId);
        })
        ->andReturn([
            'status' => $fixture['response']['status'],
            'headers' => [],
            'body' => $fixture['response']['data'],
        ]);

    $response = $this->emails->get($emailId);

    expect($response)->toBeInstanceOf(EmailResponse::class);
    expect($response->getId())->toBe($emailId);
    expect($response->getStatus())->toBe($fixture['response']['data']['status']);
    expect($response->getSubject())->toBe($fixture['response']['data']['subject']);
    expect($response->getType())->toBe($fixture['response']['data']['type']);
    expect($response->getRecipients())->toHaveCount(1);
    expect($response->getAttachments())->toHaveCount(2);
});

// --- Attachment, template variable, and typed response tests ---

test('send with Attachment objects serializes correctly', function () {
    $attachment = Attachment::fromPath(
        name: 'report.pdf',
        path: 'https://example.com/report.pdf',
        contentId: 'cid123',
        contentDisposition: 'attachment',
    );

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            $att = $options['body']['attachments'][0];

            return $att['name'] === 'report.pdf'
                && $att['path'] === 'https://example.com/report.pdf'
                && $att['content_id'] === 'cid123'
                && $att['content_disposition'] === 'attachment';
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => ['id' => 'email_123', 'status' => 'accepted', 'emails' => [], 'restricted_emails' => []],
        ]);

    $response = $this->emails->send(
        from: 'Test <test@test.com>',
        to: ['test@test.com'],
        subject: 'Test',
        html: '<p>Test</p>',
        attachments: [$attachment],
    );

    expect($response)->toBeInstanceOf(EmailResponse::class);
});

test('sendWithTemplate with TemplateVariable objects', function () {
    $var = new TemplateVariable('USER_NAME', 'string', 'John');

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            $tv = $options['body']['template_variables'][0];

            return $tv['key'] === 'USER_NAME'
                && $tv['type'] === 'string'
                && $tv['value'] === 'John';
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => ['id' => 'email_123', 'status' => 'accepted', 'emails' => [], 'restricted_emails' => []],
        ]);

    $response = $this->emails->sendWithTemplate(
        from: 'Test <test@test.com>',
        to: ['test@test.com'],
        templateId: 'tpl_123',
        templateVariables: [$var],
    );

    expect($response)->toBeInstanceOf(EmailResponse::class);
});

test('get recipients are typed RecipientResponse objects', function () {
    $fixture = loadFixture('emails/show.json');
    $emailId = $fixture['response']['data']['id'];

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => $fixture['response']['status'],
            'headers' => [],
            'body' => $fixture['response']['data'],
        ]);

    $response = $this->emails->get($emailId);

    $recipients = $response->getRecipients();
    expect($recipients)->toHaveCount(1);

    $recipient = $recipients[0];
    expect($recipient)->toBeInstanceOf(RecipientResponse::class);
    expect($recipient->getType())->toBe('to');
    expect($recipient->getStatus())->toBe('queued');
    expect($recipient->getEmailAddress())->toBe('koelpin.burdette@example.org');
    expect($recipient->getBounceType())->toBeNull();
    expect($recipient->getBounceReason())->toBeNull();
    expect($recipient->getBouncedAt())->toBeNull();
    expect($recipient->getComplaintType())->toBeNull();
    expect($recipient->getComplainedAt())->toBeNull();
    expect($recipient->isSuppressed())->toBeFalse();
    expect($recipient->getSuppressionReason())->toBeNull();
    expect($recipient->getOpenedAt())->toBeNull();
    expect($recipient->getOpenCount())->toBe(0);
    expect($recipient->getClickedAt())->toBeNull();
    expect($recipient->getClickCount())->toBe(0);
    expect($recipient->getFailedAt())->toBeNull();
    expect($recipient->getErrorMessage())->toBeNull();
    expect($recipient->getDeliveredAt())->toBeNull();
    expect($recipient->getSentAt())->toBeNull();
});

test('get attachments are typed EmailAttachmentResponse objects', function () {
    $fixture = loadFixture('emails/show.json');
    $emailId = $fixture['response']['data']['id'];

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->andReturn([
            'status' => $fixture['response']['status'],
            'headers' => [],
            'body' => $fixture['response']['data'],
        ]);

    $response = $this->emails->get($emailId);

    $attachments = $response->getAttachments();
    expect($attachments)->toHaveCount(2);

    $attachment = $attachments[0];
    expect($attachment)->toBeInstanceOf(EmailAttachmentResponse::class);
    expect($attachment->getId())->toBe('01kvv5dv461r4x1za05c0sy6nq');
    expect($attachment->getName())->toBe('I9wJnL1QeUOKnsgE.png');
    expect($attachment->getMime())->toBe('image/png');
    expect($attachment->getContentId())->toBe('XUT5SWQt5AsAsRVv');
    expect($attachment->getContentDisposition())->toBe('attachment');
    expect($attachment->getSize())->toBe(16174079);
    expect($attachment->getDownloadUrl())->toBeString();
    expect($attachment->getDownloadUrl())->not->toBeEmpty();
});

test('send with Attachment fromPath', function () {
    $attachment = Attachment::fromPath(
        name: 'invoice.pdf',
        path: 'https://example.com/invoice.pdf',
    );

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            $att = $options['body']['attachments'][0];

            return $att['name'] === 'invoice.pdf'
                && $att['path'] === 'https://example.com/invoice.pdf'
                && !isset($att['content'])
                && !isset($att['content_id']);
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => ['id' => 'email_123', 'status' => 'accepted', 'emails' => [], 'restricted_emails' => []],
        ]);

    $this->emails->send(
        from: 'Test <test@test.com>',
        to: ['test@test.com'],
        subject: 'Test',
        html: '<p>Test</p>',
        attachments: [$attachment],
    );
});

test('send with Attachment fromContent', function () {
    $attachment = Attachment::fromContent(
        name: 'data.txt',
        content: base64_encode('Hello World'),
    );

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            $att = $options['body']['attachments'][0];

            return $att['name'] === 'data.txt'
                && $att['content'] === base64_encode('Hello World')
                && !isset($att['path']);
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => ['id' => 'email_123', 'status' => 'accepted', 'emails' => [], 'restricted_emails' => []],
        ]);

    $this->emails->send(
        from: 'Test <test@test.com>',
        to: ['test@test.com'],
        subject: 'Test',
        html: '<p>Test</p>',
        attachments: [$attachment],
    );
});

test('attachment with mime serializes mime field', function () {
    $attachment = Attachment::fromPath(
        name: 'report.pdf',
        path: 'https://example.com/report.pdf',
        contentId: 'cid123',
        contentDisposition: 'attachment',
        mime: 'application/pdf',
    );

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            $att = $options['body']['attachments'][0];

            return $att['name'] === 'report.pdf'
                && $att['path'] === 'https://example.com/report.pdf'
                && $att['mime'] === 'application/pdf';
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => ['id' => 'email_123', 'status' => 'accepted', 'emails' => [], 'restricted_emails' => []],
        ]);

    $this->emails->send(
        from: 'Test <test@test.com>',
        to: ['test@test.com'],
        subject: 'Test',
        html: '<p>Test</p>',
        attachments: [$attachment],
    );
});

test('attachment without mime does not include mime field', function () {
    $attachment = Attachment::fromPath(
        name: 'report.pdf',
        path: 'https://example.com/report.pdf',
    );

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            $att = $options['body']['attachments'][0];

            return $att['name'] === 'report.pdf'
                && $att['path'] === 'https://example.com/report.pdf'
                && !isset($att['mime']);
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => ['id' => 'email_123', 'status' => 'accepted', 'emails' => [], 'restricted_emails' => []],
        ]);

    $this->emails->send(
        from: 'Test <test@test.com>',
        to: ['test@test.com'],
        subject: 'Test',
        html: '<p>Test</p>',
        attachments: [$attachment],
    );
});

test('sendWithTemplate serializes Attachment objects through toArray', function () {
    $attachment = Attachment::fromPath(
        name: 'invoice.pdf',
        path: 'https://example.com/invoice.pdf',
        contentId: 'cid-invoice',
        contentDisposition: 'attachment',
    );

    $this->transport
        ->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            $att = $options['body']['attachments'][0] ?? [];

            return $att['name'] === 'invoice.pdf'
                && $att['path'] === 'https://example.com/invoice.pdf'
                && $att['content_id'] === 'cid-invoice'
                && $att['content_disposition'] === 'attachment';
        })
        ->andReturn([
            'status' => 200,
            'headers' => [],
            'body' => ['id' => 'email_123', 'status' => 'accepted', 'emails' => [], 'restricted_emails' => []],
        ]);

    $this->emails->sendWithTemplate(
        from: 'Test <test@test.com>',
        to: ['test@test.com'],
        templateId: 'tpl_123',
        attachments: [$attachment],
    );
});
