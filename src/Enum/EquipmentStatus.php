<?php

declare(strict_types=1);

namespace App\Enum;

enum EquipmentStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Usage = 'issued';
}
