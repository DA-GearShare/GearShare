<?php

declare(strict_types=1);

namespace App\Enum;

enum ReservationItemStatus: string
{
    case Requested = 'requested';
    case Issued = 'issued';
    case Returned = 'returned';
}
