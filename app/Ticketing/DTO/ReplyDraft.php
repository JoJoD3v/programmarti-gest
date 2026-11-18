<?php

namespace App\Ticketing\DTO;

final readonly class ReplyDraft
{
    public function __construct(
        public string $text,
        public string $model,
        public string $promptVersion,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
    ) {}
}
