<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Http\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class MeController
{
    public function __construct(private readonly JWTEncoderInterface $jwtEncoder)
    {
    }

    #[Route(path: '/auth/me', name: 'auth_me', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $token = $this->extractBearerToken($request);
        if ($token === null) {
            return new JsonResponse(['error' => 'Missing bearer token.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        try {
            $payload = $this->jwtEncoder->decode($token);
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => 'Invalid token.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user_id' => $payload['user_id'] ?? null,
            'identity_id' => $payload['identity_id'] ?? null,
            'provider' => $payload['provider'] ?? null,
            'payload' => $payload,
        ]);
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->headers->get('Authorization');
        if ($header === null) {
            return null;
        }

        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));
        return $token !== '' ? $token : null;
    }
}
