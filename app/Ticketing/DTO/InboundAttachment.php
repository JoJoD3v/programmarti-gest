<?php

namespace App\Ticketing\DTO;

final readonly class InboundAttachment
{
    public function __construct(
        public string $filename,
        public string $mime,
        public int $size,
        public ?string $contentId = null,
        public bool $inline = false,
        public bool $tooLarge = false,
        public ?string $content = null,
        public ?string $storedPath = null,
    ) {}

    public function withStoredPath(string $path): self
    {
        return new self($this->filename, $this->mime, $this->size, $this->contentId, $this->inline, $this->tooLarge, null, $path);
    }
}
