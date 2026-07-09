<?php

declare(strict_types=1);

namespace LetMeSendEmail\Resources;

use LetMeSendEmail\Client;
use LetMeSendEmail\Responses\EmailTopicListResponse;
use LetMeSendEmail\Responses\EmailTopicResponse;
use LetMeSendEmail\Responses\StatusResponse;

final class EmailTopicsResource
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function create(
        string $name,
        string $slug,
        ?bool $autoSubscribe = null,
        ?bool $public = null,
        ?string $description = null,
        ?string $domainId = null,
    ): EmailTopicResponse {
        $body = [
            'name' => $name,
            'slug' => $slug,
        ];

        if ($autoSubscribe !== null) {
            $body['auto_subscribe'] = $autoSubscribe;
        }
        if ($public !== null) {
            $body['public'] = $public;
        }
        if ($description !== null) {
            $body['description'] = $description;
        }
        if ($domainId !== null) {
            $body['domain'] = ['id' => $domainId];
        }

        $data = $this->client->request('POST', '/email-topics', body: $body);

        return new EmailTopicResponse($data);
    }

    public function list(
        ?int $perPage = null,
        ?string $after = null,
        ?string $before = null,
    ): EmailTopicListResponse {
        $query = [];

        if ($perPage !== null) {
            $query['per_page'] = $perPage;
        }
        if ($after !== null) {
            $query['after'] = $after;
        }
        if ($before !== null) {
            $query['before'] = $before;
        }

        $path = '/email-topics';
        if ($query !== []) {
            $path .= '?' . http_build_query($query);
        }

        $data = $this->client->request('GET', $path);

        return new EmailTopicListResponse($data);
    }

    public function get(string $id): EmailTopicResponse
    {
        $data = $this->client->request('GET', '/email-topics/' . $id);

        return new EmailTopicResponse($data);
    }

    public function update(
        string $id,
        ?string $name = null,
        ?string $slug = null,
        ?string $description = null,
        ?bool $public = null,
        ?bool $autoSubscribe = null,
    ): EmailTopicResponse {
        $body = [];

        if ($name !== null) {
            $body['name'] = $name;
        }
        if ($slug !== null) {
            $body['slug'] = $slug;
        }
        if ($description !== null) {
            $body['description'] = $description;
        }
        if ($public !== null) {
            $body['public'] = $public;
        }
        if ($autoSubscribe !== null) {
            $body['auto_subscribe'] = $autoSubscribe;
        }

        $data = $this->client->request('PUT', '/email-topics/' . $id, body: $body);

        return new EmailTopicResponse($data);
    }

    public function delete(string $id): StatusResponse
    {
        $data = $this->client->request('DELETE', '/email-topics/' . $id);

        return new StatusResponse($data);
    }
}
