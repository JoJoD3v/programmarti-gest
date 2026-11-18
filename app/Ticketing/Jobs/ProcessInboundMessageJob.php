<?php

namespace App\Ticketing\Jobs;

use App\Ticketing\Actions\ProcessInboundMessage;
use App\Ticketing\DTO\InboundEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessInboundMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    /** @var list<int> */
    public array $backoff = [30, 120, 600];

    public function __construct(public InboundEmail $email) {}

    public function handle(ProcessInboundMessage $action): void
    {
        $action->handle($this->email);
    }
}
