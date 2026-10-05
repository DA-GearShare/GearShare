<?php

declare(strict_types=1);

namespace App\Security\OAuth;

use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.oauth_provider')]
interface OAuthProviderInterface
{
    /** Name = Client-Name in knpu_oauth2_client.yaml = Teil der URL */
    public function getName(): string;

    /** @return string[] */
    public function getScopes(): array;

    public function fetchUserData(OAuth2ClientInterface $client, AccessToken $token): OAuthUserData;
}
