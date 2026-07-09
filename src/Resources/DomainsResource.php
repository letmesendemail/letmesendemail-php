<?php

declare(strict_types=1);

namespace LetMeSendEmail\Resources;

use LetMeSendEmail\Client;
use LetMeSendEmail\Responses\DomainListResponse;
use LetMeSendEmail\Responses\DomainResponse;
use LetMeSendEmail\Responses\StatusResponse;

final class DomainsResource
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function list(
        ?int $perPage = null,
        ?string $after = null,
        ?string $before = null,
    ): DomainListResponse {
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

        $path = '/domains';
        if ($query !== []) {
            $path .= '?' . http_build_query($query);
        }

        $data = $this->client->request('GET', $path);

        return new DomainListResponse($data);
    }

    public function get(string $id): DomainResponse
    {
        $data = $this->client->request('GET', '/domains/' . $id);

        return new DomainResponse($data);
    }

    public function verify(string $domain): StatusResponse
    {
        $data = $this->client->request('POST', '/domains/verify', body: [
            'domain' => $domain,
        ]);

        return new StatusResponse($data);
    }
}
