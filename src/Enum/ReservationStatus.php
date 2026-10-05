<?php

declare(strict_types=1);

namespace App\Enum;

enum ReservationStatus: string
{
    case Requested = 'angefragt';
    case Accepted = 'angenommen';
    case Denied = 'abgelehnt';
    case Active = 'aktiv';
    case Returned = 'zurückgegeben';
    case Cancelled = 'storniert';
}
