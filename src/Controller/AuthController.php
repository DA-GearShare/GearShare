<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\OAuth\OAuthProviderRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/v1/auth', name: 'auth_')]
final class AuthController extends AbstractController
{
    #[Route('/connect/{provider}', name: 'connect_start', requirements: ['provider' => '[a-z0-9_-]+'], methods: ['GET'])]
    public function start(string $provider, OAuthProviderRegistry $providers, ClientRegistry $clients): RedirectResponse
    {
        $p = $providers->get($provider);

        return $clients->getClient($p->getName())->redirect($p->getScopes(), []);
    }

    #[Route('/connect/{provider}/check', name: 'connect_check', requirements: ['provider' => '[a-z0-9_-]+'], methods: ['GET'])]
    public function check(): Response
    {
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Security $security): Response
    {
        if ($security->getUser()) $security->logout(false);
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return new JsonResponse([
            'id' => $user->getUserId(),
            'email' => $user->getEmail(),
            'displayName' => $user->getDisplayName() ?? '',
            'isAdmin' => $user->isAdmin(),
            'isTeacher' => $user->isTeacher()
        ]);
    }
}
