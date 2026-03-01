<?php

declare(strict_types=1);

namespace App\Auth\Application\Service;

use App\Auth\Application\Dto\LoginRequest;
use App\Auth\Application\Exception\AuthenticationFailed;
use App\Auth\Application\Security\PasswordHasher;
use App\Auth\Domain\Entity\Identity;
use App\Auth\Infrastructure\Doctrine\Repository\IdentityRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final class AuthenticateUser
{
    public function __construct(
        private readonly IdentityRepository $identityRepository,
        private readonly PasswordHasher $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager
    ) {
    }

    /**
     * @return array{token:string}
     */
    public function authenticate(LoginRequest $request): array
    {
        [$provider, $identifier] = $this->resolveIdentifier($request);

        $identity = $this->identityRepository->findOneBy([
            'provider' => $provider,
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

    /**
     * @return array{0:string,1:string}
     */
    private function resolveIdentifier(LoginRequest $request): array
    {
        if ($request->email !== null && $request->email !== '') {
            return [Identity::PROVIDER_EMAIL, strtolower($request->email)];
        }

        if ($request->phone !== null && $request->phone !== '') {
            return [Identity::PROVIDER_PHONE, $request->phone];
        }

        throw new \InvalidArgumentException('Email or phone is required.');
    }
}
