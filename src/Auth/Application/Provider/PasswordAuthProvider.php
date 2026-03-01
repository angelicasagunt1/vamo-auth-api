<?php

declare(strict_types=1);

namespace App\Auth\Application\Provider;

use App\Auth\Application\Dto\ProviderLoginRequest;
use App\Auth\Application\Exception\AuthenticationFailed;
use App\Auth\Application\Security\PasswordHasher;
use App\Auth\Domain\Entity\Identity;
use App\Auth\Infrastructure\Doctrine\Repository\IdentityRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final class PasswordAuthProvider implements AuthProviderInterface
{
    public function __construct(
        private readonly IdentityRepository $identityRepository,
        private readonly PasswordHasher $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager
    ) {
    }

    public function supports(string $provider): bool
    {
        return $provider === Identity::PROVIDER_EMAIL;
    }

    public function authenticate(ProviderLoginRequest $request): array
    {
        $identifier = $this->resolveIdentifier($request);

        $identity = $this->identityRepository->findOneBy([
            'provider' => $request->provider,
            'identifier' => $identifier,
        ]);

        if ($identity === null || !$this->passwordHasher->verify($identity->getPasswordHash(), $request->password ?? '')) {
            throw new AuthenticationFailed('Invalid credentials.');
        }

        $payload = [
            'user_id' => $identity->getUser()?->getId(),
            'identity_id' => $identity->getId(),
            'provider' => $identity->getProvider(),
        ];

        $jwtUser = JWTUser::createFromPayload($identifier, $payload);
        $token = $this->jwtManager->create($jwtUser);

        return ['token' => $token];
    }

    private function resolveIdentifier(ProviderLoginRequest $request): string
    {
        if ($request->provider === Identity::PROVIDER_EMAIL) {
            return strtolower($request->email ?? '');
        }

        throw new \InvalidArgumentException('Invalid provider for password auth.');
    }
}
