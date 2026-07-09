<?php

declare(strict_types=1);

namespace LetMeSendEmail\Resources;

use LetMeSendEmail\Client;
use LetMeSendEmail\Responses\ContactCategoryListResponse;
use LetMeSendEmail\Responses\ContactCategoryResponse;
use LetMeSendEmail\Responses\StatusResponse;

final class ContactCategoriesResource
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function create(
        string $name,
        ?string $slug = null,
    ): ContactCategoryResponse {
        $body = [
            'name' => $name,
        ];

        if ($slug !== null) {
            $body['slug'] = $slug;
        }

        $data = $this->client->request('POST', '/contact-categories', body: $body);

        return new ContactCategoryResponse($data);
    }

    public function list(
        ?int $perPage = null,
        ?string $after = null,
        ?string $before = null,
    ): ContactCategoryListResponse {
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

        $path = '/contact-categories';
        if ($query !== []) {
            $path .= '?' . http_build_query($query);
        }

        $data = $this->client->request('GET', $path);

        return new ContactCategoryListResponse($data);
    }

    public function get(string $id): ContactCategoryResponse
    {
        $data = $this->client->request('GET', '/contact-categories/' . $id);

        return new ContactCategoryResponse($data);
    }

    public function update(
        string $id,
        string $name,
        ?string $slug = null,
    ): ContactCategoryResponse {
        $body = [
            'name' => $name,
        ];

        if ($slug !== null) {
            $body['slug'] = $slug;
        }

        $data = $this->client->request('PUT', '/contact-categories/' . $id, body: $body);

        return new ContactCategoryResponse($data);
    }

    public function delete(string $id): StatusResponse
    {
        $data = $this->client->request('DELETE', '/contact-categories/' . $id);

        return new StatusResponse($data);
    }
}
