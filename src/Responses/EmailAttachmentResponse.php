<?php

declare(strict_types=1);

namespace LetMeSendEmail\Responses;

final class EmailAttachmentResponse
{
    private string $id;
    private string $name;
    private string $mime;
    private string $contentId;
    private string $contentDisposition;
    private int $size;
    private string $downloadUrl;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->mime = $data['mime'] ?? '';
        $this->contentId = $data['content_id'] ?? '';
        $this->contentDisposition = $data['content_disposition'] ?? '';
        $this->size = $data['size'] ?? 0;
        $this->downloadUrl = $data['download_url'] ?? '';
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getMime(): string
    {
        return $this->mime;
    }
    public function getContentId(): string
    {
        return $this->contentId;
    }
    public function getContentDisposition(): string
    {
        return $this->contentDisposition;
    }
    public function getSize(): int
    {
        return $this->size;
    }
    public function getDownloadUrl(): string
    {
        return $this->downloadUrl;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'mime' => $this->getMime(),
            'content_id' => $this->getContentId(),
            'content_disposition' => $this->getContentDisposition(),
            'size' => $this->getSize(),
            'download_url' => $this->getDownloadUrl(),
        ];
    }
}
