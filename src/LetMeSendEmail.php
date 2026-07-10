<?php

declare(strict_types=1);

namespace LetMeSendEmail;

use GuzzleHttp\Client as GuzzleClient;
use LetMeSendEmail\Http\GuzzleTransport;
use LetMeSendEmail\Http\TransportInterface;
use LetMeSendEmail\Resources\ContactCategoriesResource;
use LetMeSendEmail\Resources\ContactsResource;
use LetMeSendEmail\Resources\DomainsResource;
use LetMeSendEmail\Resources\EmailsResource;
use LetMeSendEmail\Resources\EmailTopicsResource;

final class LetMeSendEmail
{
    private Client $client;

    public function __construct(
        ?string $apiKey = null,
        ?Configuration $configuration = null,
        ?TransportInterface $transport = null,
    ) {
        if ($configuration === null) {
            if ($apiKey === null) {
                throw new \InvalidArgumentException('Either apiKey or configuration must be provided.');
            }
            $configuration = new Configuration($apiKey);
        }

        $transport ??= new GuzzleTransport(new GuzzleClient());
        $this->client = new Client($configuration, $transport);
    }

    public function emails(): EmailsResource
    {
        return new EmailsResource($this->client);
    }

    public function domains(): DomainsResource
    {
        return new DomainsResource($this->client);
    }

    public function contacts(): ContactsResource
    {
        return new ContactsResource($this->client);
    }

    public function contactCategories(): ContactCategoriesResource
    {
        return new ContactCategoriesResource($this->client);
    }

    public function emailTopics(): EmailTopicsResource
    {
        return new EmailTopicsResource($this->client);
    }
}
