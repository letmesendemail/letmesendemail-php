<?php

declare(strict_types=1);

namespace LetMeSendEmail\Resources;

use LetMeSendEmail\Client;
use LetMeSendEmail\Responses\EmailListResponse;
use LetMeSendEmail\Responses\EmailResponse;
use LetMeSendEmail\Responses\VerifyEmailResponse;

final class EmailsResource
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function send(
        string $from,
        array $to,
        string $subject,
        ?string $html = null,
        ?string $text = null,
        ?string $type = null,
        ?string $eventName = null,
        ?string $emailTopicId = null,
        ?array $replyTo = null,
        ?array $cc = null,
        ?array $bcc = null,
        ?array $headers = null,
        ?array $attachments = null,
        ?string $idempotencyKey = null,
    ): EmailResponse {
        $body = [
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
        ];

        if ($type !== null) {
            $body['type'] = $type;
        }

        if ($html !== null) {
            $body['html'] = $html;
        }

        if ($text !== null) {
            $body['text'] = $text;
        }

        if ($eventName !== null) {
            $body['event_name'] = $eventName;
        }

        if ($emailTopicId !== null) {
            $body['email_topic_id'] = $emailTopicId;
        }

        if ($replyTo !== null) {
            $body['reply_to'] = $replyTo;
        }

        if ($cc !== null) {
            $body['cc'] = $cc;
        }

        if ($bcc !== null) {
            $body['bcc'] = $bcc;
        }

        if ($headers !== null) {
            $body['headers'] = $headers;
        }

        if ($attachments !== null) {
            $body['attachments'] = $attachments;
        }

        $requestHeaders = [];
        if ($idempotencyKey !== null) {
            $requestHeaders['Idempotency-Key'] = $idempotencyKey;
        }

        $data = $this->client->request('POST', '/emails', body: $body, headers: $requestHeaders);

        return EmailResponse::fromSendResponse($data);
    }

    public function sendWithTemplate(
        string $from,
        array $to,
        string $templateId,
        ?string $subject = null,
        ?array $templateVariables = null,
        ?string $type = null,
        ?string $eventName = null,
        ?string $emailTopicId = null,
        ?array $replyTo = null,
        ?array $cc = null,
        ?array $bcc = null,
        ?array $headers = null,
        ?array $attachments = null,
        ?string $idempotencyKey = null,
    ): EmailResponse {
        $body = [
            'from' => $from,
            'to' => $to,
            'template_id' => $templateId,
        ];

        if ($type !== null) {
            $body['type'] = $type;
        }

        if ($subject !== null) {
            $body['subject'] = $subject;
        }

        if ($templateVariables !== null) {
            $body['template_variables'] = $templateVariables;
        }

        if ($eventName !== null) {
            $body['event_name'] = $eventName;
        }

        if ($emailTopicId !== null) {
            $body['email_topic_id'] = $emailTopicId;
        }

        if ($replyTo !== null) {
            $body['reply_to'] = $replyTo;
        }

        if ($cc !== null) {
            $body['cc'] = $cc;
        }

        if ($bcc !== null) {
            $body['bcc'] = $bcc;
        }

        if ($headers !== null) {
            $body['headers'] = $headers;
        }

        if ($attachments !== null) {
            $body['attachments'] = $attachments;
        }

        $requestHeaders = [];
        if ($idempotencyKey !== null) {
            $requestHeaders['Idempotency-Key'] = $idempotencyKey;
        }

        $data = $this->client->request('POST', '/emails', body: $body, headers: $requestHeaders);

        return EmailResponse::fromSendResponse($data);
    }

    public function verify(string $email): VerifyEmailResponse
    {
        $data = $this->client->request('POST', '/emails/verify', body: [
            'email' => $email,
        ]);

        return new VerifyEmailResponse($data);
    }

    public function list(
        ?int $perPage = null,
        ?string $after = null,
        ?string $before = null,
    ): EmailListResponse {
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

        $path = '/emails';
        if ($query !== []) {
            $path .= '?' . http_build_query($query);
        }

        $data = $this->client->request('GET', $path);

        return new EmailListResponse($data);
    }

    public function get(string $id): EmailResponse
    {
        $data = $this->client->request('GET', '/emails/' . $id);

        return EmailResponse::fromShowResponse($data);
    }
}
