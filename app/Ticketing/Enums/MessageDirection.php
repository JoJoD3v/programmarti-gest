<?php

namespace App\Ticketing\Enums;

enum MessageDirection: string
{
    case In = 'in';
    case Out = 'out';
}
