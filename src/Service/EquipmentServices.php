<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Equipment;

/**
 * Shared serialization of Equipment, matching the `Equipment` schema in docs/api.yaml.
 */
trait EquipmentServices
{
    /** @return array<string, mixed> */
    private function serializeEquipment(Equipment $equipment): array
    {
        return [
            'id' => $equipment->getId()?->toRfc4122(),
            'poolId' => $equipment->getPool()?->getId()?->toRfc4122(),
            'name' => $equipment->getName(),
            'location' => $equipment->getLocation(),
            'type' => $equipment->getType(),
            'model' => $equipment->getModel(),
            'code' => $equipment->getCode(),
            'mainPhotoUrl' => $equipment->getMainPhoto()?->getUrl(),
        ];
    }
}
