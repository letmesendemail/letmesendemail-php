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
    private array $recipients;
    private array $attachments;
    private array $emails;
    private array $restrictedEmails;
    private bool $duplicate;

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
        $this->recipients = $data['recipients'] ?? [];
        $this->attachments = $data['attachments'] ?? [];
        $this->emails = $data['emails'] ?? [];
        $this->restrictedEmails = $data['restricted_emails'] ?? [];
        $this->duplicate = $data['duplicate'] ?? false;
    }

    public static function fromSendResponse(array $data): self
    {
        return new self($data);
    }

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

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getEmails(): array
    {
        return $this->emails;
    }

    public function getRestrictedEmails(): array
    {
        return $this->restrictedEmails;
    }

    public function isDuplicate(): bool
    {
        return $this->duplicate;
    }
}
