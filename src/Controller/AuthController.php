<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\OAuth\OAuthProviderRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/v1/auth', name: 'auth_')]
#[OA\Tag(name: 'Auth', description: 'OAuth2 authentication and session management')]
class AuthController extends AbstractController
{
    #[Route('/connect/{provider}', name: 'connect_start', requirements: ['provider' => '[a-z0-9_-]+'], methods: ['GET'])]
    #[OA\Get(
        operationId: 'oauthConnectStart',
        description: 'Redirects the user agent to the selected OAuth2 provider\'s authorization page. The requested scopes are determined server-side by the provider configuration. After the user has authorized the app, the provider redirects back to the callback endpoint (`/api/v1/auth/connect/{provider}/check`).',
        summary: 'Start OAuth2 login',
        security: [],
        tags: ['Auth'],
        parameters: [
            new OA\Parameter(
                name: 'provider',
                description: 'OAuth provider identifier (lowercase letters, digits, "-" and "_")',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[a-z0-9_-]+$', example: 'microsoft'),
                example: 'microsoft',
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_FOUND,
                description: 'Redirect to the provider\'s authorization URL',
                headers: [
                    new OA\Header(
                        header: 'Location',
                        description: 'Provider authorization URL',
                        schema: new OA\Schema(type: 'string', format: 'uri'),
                    ),
                ],
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Unknown OAuth provider',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'type', type: 'string', example: 'https://tools.ietf.org/html/rfc2616#section-10'),
                        new OA\Property(property: 'title', type: 'string', example: 'An error occurred'),
                        new OA\Property(property: 'status', type: 'integer', example: 404),
                        new OA\Property(property: 'detail', type: 'string', example: 'Not Found'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Unexpected server error (e.g. misconfigured OAuth client)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'type', type: 'string', example: 'https://tools.ietf.org/html/rfc2616#section-10'),
                        new OA\Property(property: 'title', type: 'string', example: 'An error occurred'),
                        new OA\Property(property: 'status', type: 'integer', example: 500),
                        new OA\Property(property: 'detail', type: 'string', example: 'Internal Server Error'),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function start(string $provider, OAuthProviderRegistry $providers, ClientRegistry $clients): RedirectResponse
    {
        $p = $providers->get($provider);

        return $clients->getClient($p->getName())->redirect($p->getScopes(), []);
    }

    #[Route('/connect/{provider}/check', name: 'connect_check', requirements: ['provider' => '[a-z0-9_-]+'], methods: ['GET'])]
    #[OA\Get(
        operationId: 'oauthConnectCheck',
        description: 'Redirect URI the provider calls after the user authorizes the app. Handled by the authenticator; not meant to be called manually. On success a session is established and the session cookie is set on the response.',
        summary: 'OAuth2 callback',
        security: [],
        tags: ['Auth'],
        parameters: [
            new OA\Parameter(
                name: 'provider',
                description: 'OAuth provider identifier',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[a-z0-9_-]+$', example: 'microsoft'),
                example: 'microsoft',
            ),
            new OA\Parameter(
                name: 'code',
                description: 'Authorization code issued by the provider',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'state',
                description: 'Opaque value used to prevent CSRF',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
            new OA\Parameter(
                name: 'error',
                description: 'Error code returned by the provider if the user denied access or the authorization failed',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'access_denied'),
            ),
            new OA\Parameter(
                name: 'error_description',
                description: 'Human-readable error description returned by the provider',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Authentication succeeded',
                headers: [
                    new OA\Header(
                        header: 'Set-Cookie',
                        description: 'Session cookie of the authenticated user',
                        schema: new OA\Schema(type: 'string'),
                    ),
                ],
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: 'Authentication failed (invalid or missing code/state, provider error, or code exchange failed)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'type', type: 'string', example: 'https://tools.ietf.org/html/rfc2616#section-10'),
                        new OA\Property(property: 'title', type: 'string', example: 'An error occurred'),
                        new OA\Property(property: 'status', type: 'integer', example: 401),
                        new OA\Property(property: 'detail', type: 'string', example: 'Authentication failed'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Unknown OAuth provider',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'type', type: 'string', example: 'https://tools.ietf.org/html/rfc2616#section-10'),
                        new OA\Property(property: 'title', type: 'string', example: 'An error occurred'),
                        new OA\Property(property: 'status', type: 'integer', example: 404),
                        new OA\Property(property: 'detail', type: 'string', example: 'Not Found'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Unexpected server error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'type', type: 'string', example: 'https://tools.ietf.org/html/rfc2616#section-10'),
                        new OA\Property(property: 'title', type: 'string', example: 'An error occurred'),
                        new OA\Property(property: 'status', type: 'integer', example: 500),
                        new OA\Property(property: 'detail', type: 'string', example: 'Internal Server Error'),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function check(): Response
    {
        return new Response('', 204);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    #[OA\Post(
        operationId: 'oauthLogout',
        description: 'Ends the current session and invalidates the session cookie. Safe to call when not authenticated (idempotent); the endpoint responds with 204 in both cases. Takes no request body.',
        summary: 'Log out',
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Logged out',
                headers: [
                    new OA\Header(
                        header: 'Set-Cookie',
                        description: 'Expired session cookie that clears the session on the client',
                        schema: new OA\Schema(type: 'string'),
                    ),
                ],
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Unexpected server error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'type', type: 'string', example: 'https://tools.ietf.org/html/rfc2616#section-10'),
                        new OA\Property(property: 'title', type: 'string', example: 'An error occurred'),
                        new OA\Property(property: 'status', type: 'integer', example: 500),
                        new OA\Property(property: 'detail', type: 'string', example: 'Internal Server Error'),
                    ],
                    type: 'object',
                ),
            ),
        ],
    )]
    public function logout(Security $security): Response
    {
        if ($security->getUser()) $security->logout(false);
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    #[OA\Get(
        operationId: 'authMe',
        description: 'Returns information about the currently logged-in user.',
        summary: 'Get current user',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Current user',
                content: new OA\JsonContent(
                    required: ['success', 'payload'],
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'payload',
                            required: ['id', 'roles'],
                            properties: [
                                new OA\Property(property: 'id', type: 'string', example: 'github:123456789'),
                                new OA\Property(property: 'displayname', type: 'string', example: 'Max Mustermann'),
                                new OA\Property(property: 'email', type: 'string', example: 'email@example.com'),
                                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_USER'],),
                            ],
                            type: 'object',
                        ),
                    ],
                ),
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Not authenticated'),
        ],
    )]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'payload' => [
                'id' => $user->getUserId(),
                'displayname' => empty($user->getDisplayName()) ? '' : $user->getDisplayName(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }
}
