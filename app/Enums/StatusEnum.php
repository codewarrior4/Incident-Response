<?php

namespace App\Enums;

enum StatusEnum: string
{
    case Open = 'open';
    case Investigating = 'investigating';
    case Resolved = 'resolved';
}
