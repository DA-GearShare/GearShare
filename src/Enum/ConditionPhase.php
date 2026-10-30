<?php

declare(strict_types=1);

namespace App\Enum;

enum ConditionPhase: string
{
    case Pickup = 'pickup';
    case Return = 'return';
}
