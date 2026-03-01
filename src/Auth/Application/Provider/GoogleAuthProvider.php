<?php

declare(strict_types=1);

namespace App\Auth\Application\Provider;

use App\Auth\Application\Dto\ProviderLoginRequest;
use App\Auth\Application\Exception\AuthenticationFailed;
use App\Auth\Application\Exception\ProviderNotConfigured;
use App\Auth\Domain\Entity\Identity;
use App\Auth\Domain\Entity\User;
use App\Auth\Application\Service\RefreshTokenService;
use App\Auth\Infrastructure\Doctrine\Repository\IdentityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class GoogleAuthProvider implements AuthProviderInterface
{
    public function __construct(
        private readonly IdentityRepository $identityRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RefreshTokenService $refreshTokenService,
        #[Autowire('%env(GOOGLE_CLIENT_ID)%')]
        private readonly string $clientId
    ) {
    }

    public function supports(string $provider): bool
    {
        return $provider === Identity::PROVIDER_GOOGLE;
    }

    public function authenticate(ProviderLoginRequest $request): array
    {
        if ($this->clientId === '') {
            throw new ProviderNotConfigured('Google provider is not configured.');
        }

        $token = $request->token ?? '';
        if ($token === '') {
            throw new AuthenticationFailed('Token is required.');
        }

        $payload = $this->validateToken($token);
        $identifier = $payload['sub'] ?? null;
        if ($identifier === null || $identifier === '') {
            throw new AuthenticationFailed('Invalid token payload.');
        }

        $identity = $this->identityRepository->findOneBy([
            'provider' => Identity::PROVIDER_GOOGLE,
            'identifier' => $identifier,
        ]);

        if ($identity === null) {
            $user = new User();
            $identity = new Identity(Identity::PROVIDER_GOOGLE, $identifier);
            $user->addIdentity($identity);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        $jwtPayload = [
            'user_id' => $identity->getUser()?->getId(),
            'identity_id' => $identity->getId(),
            'provider' => $identity->getProvider(),
        ];

        $jwtUser = JWTUser::createFromPayload($identifier, $jwtPayload);
        $jwt = $this->jwtManager->create($jwtUser);
        $refreshToken = $this->refreshTokenService->issue($identity->getUser(), $identity);

        return ['token' => $jwt, 'refresh_token' => $refreshToken];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateToken(string $token): array
    {
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($token);
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new AuthenticationFailed('Token validation failed.');
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new AuthenticationFailed('Invalid token response.');
        }

        if (($data['aud'] ?? null) !== $this->clientId) {
            throw new AuthenticationFailed('Token audience mismatch.');
        }

        return $data;
    }
}
