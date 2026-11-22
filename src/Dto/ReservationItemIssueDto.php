<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Validated payload for POST /reservations/{reservationId}/items/{itemId}/issue.
 */
final class ReservationItemIssueDto
{
    public const array ALLOWED_FIELDS = ['code'];

    private function __construct(
        public readonly ?string $code = null,
        private readonly array $errors = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $errors = [];
        foreach (array_diff(array_keys($data), self::ALLOWED_FIELDS) as $key) {
            $errors[(string)$key] = 'Unbekanntes Feld';
        }

        $code = $data['code'] ?? null;
        if (!is_string($code) || trim($code) === '') {
            $errors['code'] = 'Pflichtfeld, muss ein nicht-leerer String sein';
        }

        if ($errors !== []) return new self(errors: $errors);

        return new self(trim($code));
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
