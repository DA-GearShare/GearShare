<?php

declare(strict_types=1);

namespace App\Enum;

enum ReservationItemStatus: string
{
    case Requested = 'angefragt';
    case Issued = 'ausgegeben';
    case Returned = 'zurückgegeben';
}
