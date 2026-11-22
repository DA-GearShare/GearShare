<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Validated payload for PUT /equipment/{equipmentId} (components.schemas.EquipmentUpdate).
 *
 * Strings are trimmed. An empty or missing `code` means "keep the current code".
 */
final class EquipmentUpdateDto
{
    public const array ALLOWED_FIELDS = ['name', 'location', 'type', 'model', 'code'];
    private const array REQUIRED_STRINGS = ['name', 'location', 'type', 'model'];
    private const int MAX_LENGTH = 255;

    /**
     * @param array<string, string> $errors
     */
    private function __construct(
        public readonly string  $name = '',
        public readonly string  $location = '',
        public readonly string  $type = '',
        public readonly string  $model = '',
        public readonly ?string $code = null,
        private readonly array  $errors = [],
    ) {
    }

    /**
     * @param array<string|int, mixed> $data decoded JSON object
     */
    public static function fromArray(array $data): self
    {
        $errors = [];
        $values = [];

        foreach (array_diff(array_keys($data), self::ALLOWED_FIELDS) as $key) {
            $errors[(string) $key] = 'Unbekanntes Feld';
        }

        foreach (self::REQUIRED_STRINGS as $field) {
            if (!array_key_exists($field, $data)) {
                $errors[$field] = 'Pflichtfeld';
            } elseif (!is_string($data[$field])) {
                $errors[$field] = 'Muss ein String sein';
            } elseif (($value = trim($data[$field])) === '') {
                $errors[$field] = 'Darf nicht leer sein';
            } elseif (mb_strlen($value) > self::MAX_LENGTH) {
                $errors[$field] = sprintf('Maximal %d Zeichen', self::MAX_LENGTH);
            } else {
                $values[$field] = $value;
            }
        }

        $code = null;
        if (isset($data['code'])) {
            if (!is_string($data['code'])) {
                $errors['code'] = 'Muss ein String oder null sein';
            } else {
                $code = trim($data['code']);
                if (mb_strlen($code) > self::MAX_LENGTH) {
                    $errors['code'] = sprintf('Maximal %d Zeichen', self::MAX_LENGTH);
                }
                $code = $code === '' ? null : $code;
            }
        }

        if ($errors !== []) {
            return new self(errors: $errors);
        }

        return new self(
            $values['name'],
            $values['location'],
            $values['type'],
            $values['model'],
            $code,
        );
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
