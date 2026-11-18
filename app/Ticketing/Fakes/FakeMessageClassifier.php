<?php

namespace App\Ticketing\Fakes;

use App\Ticketing\Contracts\MessageClassifier;
use App\Ticketing\DTO\Classification;
use App\Ticketing\DTO\InboundEmail;
use Illuminate\Support\Collection;

class FakeMessageClassifier implements MessageClassifier
{
    public ?Classification $result = null;

    public ?\Throwable $failWith = null;

    public int $calls = 0;

    public function classify(InboundEmail $email, Collection $ticketTypes): Classification
    {
        $this->calls++;

        if ($this->failWith) {
            throw $this->failWith;
        }

        return $this->result ?? new Classification('altro', 0.5, 'llm', language: 'it', summary: mb_substr($email->subject, 0, 200));
    }
}
