<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Uid\Uuid;

/**
 * Validated payload for POST /reservations/{reservationId}/issue.
 */
final class ReservationIssueScanDto
{
    public const array ALLOWED_FIELDS = ['code', 'confirmAdd', 'replaceItemId'];

    private function __construct(
        public readonly ?string $code = null,
        public readonly bool $confirmAdd = false,
        public readonly ?Uuid $replaceItemId = null,
        private readonly array $errors = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $errors = [];
        foreach (array_diff(array_keys($data), self::ALLOWED_FIELDS) as $key) {
            $errors[(string) $key] = 'Unbekanntes Feld';
        }

        $code = $data['code'] ?? null;
        if (!\is_string($code) || trim($code) === '') {
            $errors['code'] = 'Pflichtfeld, muss ein nicht-leerer String sein';
        }

        $confirmAdd = $data['confirmAdd'] ?? false;
        if (!\is_bool($confirmAdd)) {
            $errors['confirmAdd'] = 'Muss ein Boolean sein';
        }

        $replace = $data['replaceItemId'] ?? null;
        if ($replace !== null && (!\is_string($replace) || !Uuid::isValid($replace))) {
            $errors['replaceItemId'] = 'Muss eine UUID oder null sein';
        } elseif ($replace !== null && $confirmAdd !== true) {
            $errors['replaceItemId'] = 'Nur zusammen mit confirmAdd: true erlaubt';
        }

        if ($errors !== []) {
            return new self(errors: $errors);
        }

        return new self(trim($code), $confirmAdd, $replace !== null ? Uuid::fromString($replace) : null);
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
