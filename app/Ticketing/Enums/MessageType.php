<?php

namespace App\Ticketing\Enums;

enum MessageType: string
{
    case Reply = 'reply';
    case System = 'system';
}
