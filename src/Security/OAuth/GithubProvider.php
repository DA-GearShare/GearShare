<?php

declare(strict_types=1);

namespace App\Security\OAuth;

use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GithubProvider implements OAuthProviderInterface
{
    public function __construct(private readonly HttpClientInterface $http)
    {
    }

    public function getScopes(): array
    {
        return ['read:user', 'user:email'];
    }

    public function fetchUserData(OAuth2ClientInterface $client, AccessToken $token): OAuthUserData
    {
        $gh = $client->fetchUserFromToken($token);

        $email = $gh->getEmail() ?? $this->fetchPrimaryEmail($token->getToken());
        if (!$email) {
            throw new AuthenticationException('No verified email available from GitHub.');
        }

        return new OAuthUserData(
            provider: 'github',
            providerUserId: (string)$gh->getId(),
            email: $email,
            displayName: $gh->getName() ?? $gh->getNickname(),
        );
    }

    private function fetchPrimaryEmail(string $token): ?string
    {
        $emails = $this->http->request('GET', 'https://api.github.com/user/emails', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/vnd.github+json',
            ],
        ])->toArray(false);

        foreach ($emails as $e) {
            if (($e['primary'] ?? false) && ($e['verified'] ?? false)) {
                return $e['email'];
            }
        }

        return null;
    }

    public function getName(): string
    {
        return 'github';
    }
}
