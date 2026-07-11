<?php

declare(strict_types=1);

namespace LetMeSendEmail\Responses;

final class VerifyEmailResponse
{
    private string $email;
    private int $score;
    private string $status;
    private bool $domainExists;
    private bool $disposable;
    private bool $roleBased;
    private bool $hasMailbox;
    private bool $receiveEmail;
    private bool $mxRecords;
    private bool $validSyntax;
    private ?string $belongsTo;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->email = $data['email'] ?? '';
        $this->score = $data['score'] ?? 0;
        $this->status = $data['status'] ?? '';
        $this->domainExists = $data['domain_exists'] ?? false;
        $this->disposable = $data['disposable'] ?? false;
        $this->roleBased = $data['role_based'] ?? false;
        $this->hasMailbox = $data['has_mailbox'] ?? false;
        $this->receiveEmail = $data['receive_email'] ?? false;
        $this->mxRecords = $data['mx_records'] ?? false;
        $this->validSyntax = $data['valid_syntax'] ?? false;
        $this->belongsTo = $data['belongs_to'] ?? null;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isDomainExists(): bool
    {
        return $this->domainExists;
    }

    public function isDisposable(): bool
    {
        return $this->disposable;
    }

    public function isRoleBased(): bool
    {
        return $this->roleBased;
    }

    public function hasMailbox(): bool
    {
        return $this->hasMailbox;
    }

    public function canReceiveEmail(): bool
    {
        return $this->receiveEmail;
    }

    public function hasMxRecords(): bool
    {
        return $this->mxRecords;
    }

    public function hasValidSyntax(): bool
    {
        return $this->validSyntax;
    }

    public function getBelongsTo(): ?string
    {
        return $this->belongsTo;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'email' => $this->getEmail(),
            'score' => $this->getScore(),
            'status' => $this->getStatus(),
            'domain_exists' => $this->isDomainExists(),
            'disposable' => $this->isDisposable(),
            'role_based' => $this->isRoleBased(),
            'has_mailbox' => $this->hasMailbox(),
            'receive_email' => $this->canReceiveEmail(),
            'mx_records' => $this->hasMxRecords(),
            'valid_syntax' => $this->hasValidSyntax(),
            'belongs_to' => $this->getBelongsTo(),
        ];
    }
}
