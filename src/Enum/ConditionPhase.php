<?php

declare(strict_types=1);

namespace App\Enum;

enum ConditionPhase: string
{
    case Output = 'ausgabe';
    case Return = 'rückgabe';
}
