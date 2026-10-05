<?php

declare(strict_types=1);

namespace App\Security\OAuth;

use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class MicrosoftProvider implements OAuthProviderInterface
{
    public function getName(): string
    {
        return 'microsoft';
    }

    public function getScopes(): array
    {
        return ['openid', 'profile', 'email'];
    }

    public function fetchUserData(OAuth2ClientInterface $client, AccessToken $token): OAuthUserData
    {
        $ms = $client->fetchUserFromToken($token);
        $claims = $ms->toArray();

        $email = $claims['email'] ?? $claims['preferred_username'] ?? null;
        if (!$email) {
            throw new AuthenticationException('No email available from Microsoft.');
        }

        return new OAuthUserData(
            provider: 'microsoft',
            providerUserId: $claims['sub'] ?? $claims['oid'],
            email: $email,
            displayName: $claims['name'] ?? null,
        );
    }
}
