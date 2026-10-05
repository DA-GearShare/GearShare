<?php

declare(strict_types=1);

namespace App\Enum;

enum EquipmentStatus: string
{
    case Available = 'verfügbar';
    case Reserved = 'reserviert';
    case Usage = 'ausgegeben';
}
