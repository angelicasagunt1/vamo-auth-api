<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Http\Controller;

use App\Auth\Application\Service\RefreshTokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class RefreshController
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
        private readonly JWTTokenManagerInterface $jwtManager
    ) {
    }

    #[Route(path: '/auth/refresh', name: 'auth_refresh', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $request->toArray();
        } catch (\JsonException) {
            return new JsonResponse(['error' => 'Invalid JSON payload.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $refreshToken = isset($data['refresh_token']) ? (string) $data['refresh_token'] : '';
        if ($refreshToken === '') {
            return new JsonResponse(['error' => 'Refresh token is required.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->refreshTokenService->rotate($refreshToken);
        } catch (\RuntimeException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $identity = $result['identity'];
        $user = $result['user'];

        $payload = [
            'user_id' => $user->getId(),
            'identity_id' => $identity?->getId(),
            'provider' => $identity?->getProvider(),
        ];

        $subject = $identity?->getIdentifier() ?? (string) $user->getId();
        $jwtUser = JWTUser::createFromPayload($subject, $payload);
        $accessToken = $this->jwtManager->create($jwtUser);

        return new JsonResponse([
            'token' => $accessToken,
            'refresh_token' => $result['token'],
        ]);
    }
}
