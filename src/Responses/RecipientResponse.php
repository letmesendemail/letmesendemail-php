<?php

declare(strict_types=1);

namespace LetMeSendEmail\Responses;

final class RecipientResponse
{
    private string $type;
    private string $status;
    private string $emailAddress;
    private ?string $bounceType;
    private ?string $bounceReason;
    private ?string $bouncedAt;
    private ?string $complaintType;
    private ?string $complainedAt;
    private bool $isSuppressed;
    private ?string $suppressionReason;
    private ?string $openedAt;
    private int $openCount;
    private ?string $clickedAt;
    private int $clickCount;
    private ?string $failedAt;
    private ?string $errorMessage;
    private ?string $deliveredAt;
    private ?string $sentAt;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->type = $data['type'] ?? '';
        $this->status = $data['status'] ?? '';
        $this->emailAddress = $data['email_address'] ?? '';
        $this->bounceType = $data['bounce_type'] ?? null;
        $this->bounceReason = $data['bounce_reason'] ?? null;
        $this->bouncedAt = $data['bounced_at'] ?? null;
        $this->complaintType = $data['complaint_type'] ?? null;
        $this->complainedAt = $data['complained_at'] ?? null;
        $this->isSuppressed = $data['is_suppressed'] ?? false;
        $this->suppressionReason = $data['suppression_reason'] ?? null;
        $this->openedAt = $data['opened_at'] ?? null;
        $this->openCount = $data['open_count'] ?? 0;
        $this->clickedAt = $data['clicked_at'] ?? null;
        $this->clickCount = $data['click_count'] ?? 0;
        $this->failedAt = $data['failed_at'] ?? null;
        $this->errorMessage = $data['error_message'] ?? null;
        $this->deliveredAt = $data['delivered_at'] ?? null;
        $this->sentAt = $data['sent_at'] ?? null;
    }

    public function getType(): string
    {
        return $this->type;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }
    public function getBounceType(): ?string
    {
        return $this->bounceType;
    }
    public function getBounceReason(): ?string
    {
        return $this->bounceReason;
    }
    public function getBouncedAt(): ?string
    {
        return $this->bouncedAt;
    }
    public function getComplaintType(): ?string
    {
        return $this->complaintType;
    }
    public function getComplainedAt(): ?string
    {
        return $this->complainedAt;
    }
    public function isSuppressed(): bool
    {
        return $this->isSuppressed;
    }
    public function getSuppressionReason(): ?string
    {
        return $this->suppressionReason;
    }
    public function getOpenedAt(): ?string
    {
        return $this->openedAt;
    }
    public function getOpenCount(): int
    {
        return $this->openCount;
    }
    public function getClickedAt(): ?string
    {
        return $this->clickedAt;
    }
    public function getClickCount(): int
    {
        return $this->clickCount;
    }
    public function getFailedAt(): ?string
    {
        return $this->failedAt;
    }
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
    public function getDeliveredAt(): ?string
    {
        return $this->deliveredAt;
    }
    public function getSentAt(): ?string
    {
        return $this->sentAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'status' => $this->getStatus(),
            'email_address' => $this->getEmailAddress(),
            'bounce_type' => $this->getBounceType(),
            'bounce_reason' => $this->getBounceReason(),
            'bounced_at' => $this->getBouncedAt(),
            'complaint_type' => $this->getComplaintType(),
            'complained_at' => $this->getComplainedAt(),
            'is_suppressed' => $this->isSuppressed(),
            'suppression_reason' => $this->getSuppressionReason(),
            'opened_at' => $this->getOpenedAt(),
            'open_count' => $this->getOpenCount(),
            'clicked_at' => $this->getClickedAt(),
            'click_count' => $this->getClickCount(),
            'failed_at' => $this->getFailedAt(),
            'error_message' => $this->getErrorMessage(),
            'delivered_at' => $this->getDeliveredAt(),
            'sent_at' => $this->getSentAt(),
        ];
    }
}
