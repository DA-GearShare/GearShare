<?php

declare(strict_types=1);

namespace App\Security\OAuth;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class OAuthProviderRegistry
{
    /** @var array<string, OAuthProviderInterface> */
    private array $providers = [];

    public function __construct(
        #[AutowireIterator('app.oauth_provider')] iterable $providers,
    ) {
        foreach ($providers as $provider) {
            $this->providers[$provider->getName()] = $provider;
        }
    }

    public function get(string $name): OAuthProviderInterface
    {
        return $this->providers[$name]
            ?? throw new NotFoundHttpException(sprintf('Unknown OAuth provider "%s".', $name));
    }
}
