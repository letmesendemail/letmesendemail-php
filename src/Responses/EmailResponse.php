<?php

declare(strict_types=1);

namespace LetMeSendEmail\Responses;

final class EmailResponse
{
    private string $id;
    private string $status;
    private ?string $subject;
    private ?string $eventName;
    private ?string $type;
    private ?string $createdAt;
    private ?string $sentAt;
    private ?int $recipientsCount;
    private ?int $attachmentsCount;
    /** @var RecipientResponse[] */
    private array $recipients;
    /** @var EmailAttachmentResponse[] */
    private array $attachments;
    /** @var string[] */
    private array $emails;
    /** @var string[] */
    private array $restrictedEmails;
    private bool $duplicate;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
        $this->status = $data['status'] ?? '';
        $this->subject = $data['subject'] ?? null;
        $this->eventName = $data['event_name'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->sentAt = $data['sent_at'] ?? null;
        $this->recipientsCount = $data['recipients_count'] ?? null;
        $this->attachmentsCount = $data['attachments_count'] ?? null;
        $this->emails = $data['emails'] ?? [];
        $this->restrictedEmails = $data['restricted_emails'] ?? [];
        $this->duplicate = $data['duplicate'] ?? false;

        $this->recipients = [];
        if (isset($data['recipients'])) {
            foreach ($data['recipients'] as $recipient) {
                $this->recipients[] = new RecipientResponse($recipient);
            }
        }

        $this->attachments = [];
        if (isset($data['attachments'])) {
            foreach ($data['attachments'] as $attachment) {
                $this->attachments[] = new EmailAttachmentResponse($attachment);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromSendResponse(array $data): self
    {
        return new self($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromShowResponse(array $data): self
    {
        return new self($data);
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function getSubject(): ?string
    {
        return $this->subject;
    }
    public function getEventName(): ?string
    {
        return $this->eventName;
    }
    public function getType(): ?string
    {
        return $this->type;
    }
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }
    public function getSentAt(): ?string
    {
        return $this->sentAt;
    }
    public function getRecipientsCount(): ?int
    {
        return $this->recipientsCount;
    }
    public function getAttachmentsCount(): ?int
    {
        return $this->attachmentsCount;
    }
    /** @return RecipientResponse[] */
    public function getRecipients(): array
    {
        return $this->recipients;
    }
    /** @return EmailAttachmentResponse[] */
    public function getAttachments(): array
    {
        return $this->attachments;
    }
    /** @return string[] */
    public function getEmails(): array
    {
        return $this->emails;
    }
    /** @return string[] */
    public function getRestrictedEmails(): array
    {
        return $this->restrictedEmails;
    }
    public function isDuplicate(): bool
    {
        return $this->duplicate;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'status' => $this->getStatus(),
            'subject' => $this->getSubject(),
            'event_name' => $this->getEventName(),
            'type' => $this->getType(),
            'created_at' => $this->getCreatedAt(),
            'sent_at' => $this->getSentAt(),
            'recipients_count' => $this->getRecipientsCount(),
            'attachments_count' => $this->getAttachmentsCount(),
            'recipients' => array_map(fn (RecipientResponse $r) => $r->toArray(), $this->getRecipients()),
            'attachments' => array_map(fn (EmailAttachmentResponse $a) => $a->toArray(), $this->getAttachments()),
            'emails' => $this->getEmails(),
            'restricted_emails' => $this->getRestrictedEmails(),
            'duplicate' => $this->isDuplicate(),
        ];
    }
}
