<?php

declare(strict_types=1);

namespace App\Security\OAuth;

final readonly class OAuthUserData
{
    public function __construct(
        public string  $provider,
        public string  $providerUserId,
        public string  $email,
        public ?string $displayName = null,
    ) {
    }

    public function userAuthId(): string
    {
        return $this->provider.':'.$this->providerUserId;
    }
}
