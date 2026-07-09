<?php

declare(strict_types=1);

namespace LetMeSendEmail\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use LetMeSendEmail\Exceptions\NetworkError;
use LetMeSendEmail\Exceptions\TimeoutError;

final class GuzzleTransport implements TransportInterface
{
    private GuzzleClient $client;

    public function __construct(GuzzleClient $client)
    {
        $this->client = $client;
    }

    public function request(string $method, string $uri, array $options = []): array
    {
        try {
            $guzzleOptions = [];

            if (isset($options['headers'])) {
                $guzzleOptions['headers'] = $options['headers'];
            }

            if (isset($options['body'])) {
                $guzzleOptions['json'] = $options['body'];
            }

            if (isset($options['timeout'])) {
                $guzzleOptions['timeout'] = $options['timeout'];
            }

            $response = $this->client->request($method, $uri, $guzzleOptions);

            $status = $response->getStatusCode();
            $headers = $response->getHeaders();
            $body = json_decode((string) $response->getBody(), true);

            return [
                'status' => $status,
                'headers' => $headers,
                'body' => $body,
            ];
        } catch (GuzzleConnectException $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
                throw TimeoutError::fromRequest(
                    message: 'Request timed out.',
                    previous: $e,
                );
            }

            throw NetworkError::fromRequest(
                message: 'Unable to connect to the API.',
                previous: $e,
            );
        } catch (TooManyRedirectsException $e) {
            throw NetworkError::fromRequest(
                message: 'Too many redirects.',
                previous: $e,
            );
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $status = $response->getStatusCode();
                $headers = $response->getHeaders();
                $body = json_decode((string) $response->getBody(), true);

                return [
                    'status' => $status,
                    'headers' => $headers,
                    'body' => $body,
                ];
            }

            throw NetworkError::fromRequest(
                message: $e->getMessage(),
                previous: $e,
            );
        } catch (GuzzleException $e) {
            throw NetworkError::fromRequest(
                message: $e->getMessage(),
                previous: $e,
            );
        }
    }
}
