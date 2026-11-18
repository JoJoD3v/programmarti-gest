<?php

namespace App\Ticketing\DTO;

final readonly class SyncCursor
{
    public function __construct(
        public ?string $uidValidity = null,
        public ?int $lastUid = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->uidValidity === null || $this->lastUid === null;
    }
}
