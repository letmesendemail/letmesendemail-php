<?php

declare(strict_types=1);

namespace LetMeSendEmail\Requests;

final class Attachment
{
    private string $name;
    private ?string $path;
    private ?string $content;
    private ?string $contentId;
    private ?string $contentDisposition;
    private ?string $mime;

    private function __construct(
        string $name,
        ?string $path = null,
        ?string $content = null,
        ?string $contentId = null,
        ?string $contentDisposition = null,
        ?string $mime = null,
    ) {
        $this->name = $name;
        $this->path = $path;
        $this->content = $content;
        $this->contentId = $contentId;
        $this->contentDisposition = $contentDisposition;
        $this->mime = $mime;
    }

    /**
     * Create an attachment from a file path (URL).
     */
    public static function fromPath(
        string $name,
        string $path,
        ?string $contentId = null,
        ?string $contentDisposition = null,
        ?string $mime = null,
    ): self {
        return new self($name, path: $path, contentId: $contentId, contentDisposition: $contentDisposition, mime: $mime);
    }

    /**
     * Create an attachment from base64-encoded content.
     */
    public static function fromContent(
        string $name,
        string $content,
        ?string $contentId = null,
        ?string $contentDisposition = null,
        ?string $mime = null,
    ): self {
        return new self($name, content: $content, contentId: $contentId, contentDisposition: $contentDisposition, mime: $mime);
    }

    /**
     * @return array{name: string, path?: string, content?: string, content_id?: string, content_disposition?: string, mime?: string}
     */
    public function toArray(): array
    {
        $data = ['name' => $this->name];

        if ($this->path !== null) {
            $data['path'] = $this->path;
        }

        if ($this->content !== null) {
            $data['content'] = $this->content;
        }

        if ($this->contentId !== null) {
            $data['content_id'] = $this->contentId;
        }

        if ($this->contentDisposition !== null) {
            $data['content_disposition'] = $this->contentDisposition;
        }

        if ($this->mime !== null) {
            $data['mime'] = $this->mime;
        }

        return $data;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
