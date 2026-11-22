<?php

declare(strict_types=1);

namespace App\Dto;

use DateInvalidTimeZoneException;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Symfony\Component\Uid\Uuid;

/**
 * Validated payload for POST /reservations (components.schemas.ReservationCreate).
 *
 * Checks shape and format only; existence and availability of equipment/pools are checked in the repository.
 *
 * Pools sind vorerst deaktiviert (folgen später): `pools` wird als unbekanntes
 * Feld abgelehnt. Die Logik ist unten auskommentiert (Markierung "Pools: später").
 */
final class ReservationCreateDto
{
    public const array ALLOWED_FIELDS = ['startDatetime', 'endDatetime', 'equipmentIds'];
    // Pools: später
    // public const array ALLOWED_FIELDS = ['startDatetime', 'endDatetime', 'equipmentIds', 'pools'];
    // private const array ALLOWED_POOL_FIELDS = ['poolId', 'quantity'];
    private const string DATETIME_PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/i';

    /**
     * @param list<string> $equipmentIds lowercase RFC 4122 UUIDs, unique
     * @param array<string, string> $errors
     */
    // Pools: später – zusätzlich @param array<string, int> $pools lowercase pool UUID => quantity (>= 1)
    private function __construct(
        public readonly ?DateTimeImmutable $startDatetime = null,
        public readonly ?DateTimeImmutable $endDatetime = null,
        public readonly array              $equipmentIds = [],
        // Pools: später
        // public readonly array              $pools = [],
        private readonly array             $errors = [],
    )
    {
    }

    /**
     * @param array<string|int, mixed> $data decoded JSON object
     * @throws DateInvalidTimeZoneException
     */
    public static function fromArray(array $data): self
    {
        $errors = [];

        foreach (array_diff(array_keys($data), self::ALLOWED_FIELDS) as $key) {
            $errors[(string)$key] = 'Unbekanntes Feld';
        }

        $start = self::parseDatetime($data, 'startDatetime', $errors);
        $end = self::parseDatetime($data, 'endDatetime', $errors);

        if ($start !== null && $end !== null) {
            if ($end <= $start) {
                $errors['endDatetime'] = 'Muss nach startDatetime liegen';
            }
            if ($start < new DateTimeImmutable()) {
                $errors['startDatetime'] = 'Darf nicht in der Vergangenheit liegen';
            }
        }

        $equipmentIds = self::parseEquipmentIds($data, $errors);

        if (!isset($errors['equipmentIds']) && $equipmentIds === []) {
            $errors['equipmentIds'] = 'Mindestens ein Gerät angeben';
        }

        if ($errors !== []) {
            return new self(errors: $errors);
        }

        return new self($start, $end, $equipmentIds);
        // Pools: später
        // return new self($start, $end, $equipmentIds, $pools);
    }

    /**
     * @param array<string|int, mixed> $data
     * @param array<string, string> $errors
     * @throws DateInvalidTimeZoneException
     */
    private static function parseDatetime(array $data, string $field, array &$errors): ?DateTimeImmutable
    {
        if (!array_key_exists($field, $data)) {
            $errors[$field] = 'Pflichtfeld';
            return null;
        }

        $value = $data[$field];
        if (!is_string($value) || preg_match(self::DATETIME_PATTERN, $value) !== 1) {
            $errors[$field] = 'Muss ein Zeitpunkt im Format RFC 3339 sein, z. B. 2026-11-20T08:00:00Z';
            return null;
        }

        try {
            $parsed = new DateTimeImmutable($value);
        } catch (Exception) {
            $errors[$field] = 'Ungültiger Zeitpunkt';
            return null;
        }
        // PHP silently rolls over impossible dates (2026-02-30 -> 2026-03-02) and only warns.
        $parseErrors = DateTimeImmutable::getLastErrors();
        if ($parseErrors !== false && $parseErrors['warning_count'] > 0) {
            $errors[$field] = 'Ungültiger Zeitpunkt';
            return null;
        }

        // Doctrine stores datetimes without offset, so normalize to the server timezone.
        return $parsed->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }

    /**
     * @param array<string|int, mixed> $data
     * @param array<string, string> $errors
     * @return list<string>
     */
    private static function parseEquipmentIds(array $data, array &$errors): array
    {
        $raw = $data['equipmentIds'] ?? [];
        if (!is_array($raw) || !array_is_list($raw)) {
            $errors['equipmentIds'] = 'Muss ein Array von UUIDs sein';
            return [];
        }

        $ids = [];
        foreach ($raw as $index => $id) {
            $path = sprintf('equipmentIds[%d]', $index);
            if (!is_string($id) || !Uuid::isValid($id)) {
                $errors[$path] = 'Muss eine UUID sein';
                continue;
            }
            $id = strtolower($id);
            if (in_array($id, $ids, true)) {
                $errors[$path] = 'Gerät ist doppelt angegeben';
                continue;
            }
            $ids[] = $id;
        }

        return $ids;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
