<?php

declare(strict_types=1);

namespace LetMeSendEmail\Resources;

use LetMeSendEmail\Client;
use LetMeSendEmail\Responses\ContactListResponse;
use LetMeSendEmail\Responses\ContactResponse;
use LetMeSendEmail\Responses\ContactUpdateResponse;
use LetMeSendEmail\Responses\StatusResponse;

final class ContactsResource
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string[]|null $categories
     * @param string[]|null $emailTopics
     */
    public function create(
        string $email,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $phone = null,
        ?bool $isGloballyUnsubscribed = null,
        ?array $categories = null,
        ?array $emailTopics = null,
    ): ContactResponse {
        $body = [
            'email' => $email,
        ];

        if ($firstName !== null) {
            $body['first_name'] = $firstName;
        }
        if ($lastName !== null) {
            $body['last_name'] = $lastName;
        }
        if ($phone !== null) {
            $body['phone'] = $phone;
        }
        if ($isGloballyUnsubscribed !== null) {
            $body['is_globally_unsubscribed'] = $isGloballyUnsubscribed;
        }
        if ($categories !== null) {
            $body['categories'] = $categories;
        }
        if ($emailTopics !== null) {
            $body['email_topics'] = $emailTopics;
        }

        $data = $this->client->request('POST', '/contacts', body: $body);

        return new ContactResponse($data);
    }

    public function list(
        ?int $perPage = null,
        ?string $after = null,
        ?string $before = null,
    ): ContactListResponse {
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

        $path = '/contacts';
        if ($query !== []) {
            $path .= '?' . http_build_query($query);
        }

        $data = $this->client->request('GET', $path);

        return new ContactListResponse($data);
    }

    public function get(string $id): ContactResponse
    {
        $data = $this->client->request('GET', '/contacts/' . $id);

        return new ContactResponse($data);
    }

    /**
     * @param string[]|null $categories
     * @param string[]|null $emailTopics
     */
    public function update(
        string $id,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $phone = null,
        ?bool $isGloballyUnsubscribed = null,
        ?array $categories = null,
        ?array $emailTopics = null,
        ?bool $syncCategories = null,
        ?bool $syncEmailTopics = null,
    ): ContactUpdateResponse {
        $body = [];

        if ($firstName !== null) {
            $body['first_name'] = $firstName;
        }
        if ($lastName !== null) {
            $body['last_name'] = $lastName;
        }
        if ($phone !== null) {
            $body['phone'] = $phone;
        }
        if ($isGloballyUnsubscribed !== null) {
            $body['is_globally_unsubscribed'] = $isGloballyUnsubscribed;
        }
        if ($categories !== null) {
            $body['categories'] = $categories;
        }
        if ($emailTopics !== null) {
            $body['email_topics'] = $emailTopics;
        }
        if ($syncCategories !== null) {
            $body['sync_categories'] = $syncCategories;
        }
        if ($syncEmailTopics !== null) {
            $body['sync_email_topics'] = $syncEmailTopics;
        }

        $data = $this->client->request('PUT', '/contacts/' . $id, body: $body);

        return new ContactUpdateResponse($data);
    }

    public function delete(string $id): StatusResponse
    {
        $data = $this->client->request('DELETE', '/contacts/' . $id);

        return new StatusResponse($data);
    }
}
