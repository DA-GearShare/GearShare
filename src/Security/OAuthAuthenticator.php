<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Security\OAuth\OAuthProviderRegistry;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class OAuthAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private readonly ClientRegistry                            $clientRegistry,
        private readonly OAuthProviderRegistry                     $providers,
        private readonly EntityManagerInterface                    $em,
        #[Autowire('%env(FRONTEND_URL)%')] private readonly string $frontendUrl,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'auth_connect_check';
    }

    public function authenticate(Request $request): Passport
    {
        $provider = $this->providers->get($request->attributes->get('provider'));
        $client = $this->clientRegistry->getClient($provider->getName());

        $token = $this->fetchAccessToken($client);
        $data = $provider->fetchUserData($client, $token);

        return new SelfValidatingPassport(
            new UserBadge($data->userAuthId(), function (string $userAuthId) use ($data) {
                $user = $this->em->getRepository(User::class)
                    ->findOneBy(['oAuthUserId' => $userAuthId]);

                if (!$user) {
                    $user = new User(oAuthUserId: $userAuthId, email: $data->email);
                    $this->em->persist($user);
                }

                $user->setEmail($data->email);
                $user->setDisplayName($data->displayName);
                $this->em->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->frontendUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response($exception->getMessageKey(), Response::HTTP_FORBIDDEN);
    }
}
