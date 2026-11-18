<?php

namespace App\Ticketing\Actions;

use RuntimeException;

class SendLimitReached extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Limite giornaliero di invio raggiunto.');
    }
}
