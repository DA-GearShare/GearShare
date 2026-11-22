<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Validated payload for PATCH /users/{userId}/roles.
 *
 * null means "field not sent" (an explicit JSON null is rejected as an error).
 */
final class UserRolesDto
{
    public const array ALLOWED_FIELDS = ['isAdmin', 'isTeacher'];

    /**
     * @param array<string, string> $errors
     */
    private function __construct(
        public readonly ?bool $isAdmin,
        public readonly ?bool $isTeacher,
        private readonly array $errors = [],
    ) {
    }

    /**
     * @param array<string|int, mixed> $data decoded JSON object
     */
    public static function fromArray(array $data): self
    {
        $errors = [];

        foreach (array_diff(array_keys($data), self::ALLOWED_FIELDS) as $key) {
            $errors[(string)$key] = 'Unbekanntes Feld';
        }

        foreach (self::ALLOWED_FIELDS as $field) {
            if (array_key_exists($field, $data) && !is_bool($data[$field])) {
                $errors[$field] = 'Muss ein Boolean sein';
            }
        }

        if (!array_intersect(self::ALLOWED_FIELDS, array_keys($data))) {
            $errors['body'] = 'Mindestens eines von isAdmin oder isTeacher angeben';
        }

        if ($errors !== []) {
            return new self(null, null, $errors);
        }

        return new self(
            $data['isAdmin'] ?? null,
            $data['isTeacher'] ?? null,
        );
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * True if the request tries to revoke admin rights.
     */
    public function revokesAdmin(): bool
    {
        return $this->isAdmin === false;
    }
}
