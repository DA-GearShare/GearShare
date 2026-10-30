<?php

declare(strict_types=1);

namespace App\Enum;

enum ReservationStatus: string
{
    case Requested = 'requested';
    case Accepted = 'accepted';
    case Denied = 'denied';
    case Active = 'active';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
}
