<?php

declare(strict_types=1);

namespace App\Auth\Application\Service;

use App\Auth\Application\Dto\RegisterRequest;
use App\Auth\Application\Exception\IdentityAlreadyExists;
use App\Auth\Application\Security\PasswordHasher;
use App\Auth\Domain\Entity\Identity;
use App\Auth\Domain\Entity\User;
use App\Auth\Infrastructure\Doctrine\Repository\IdentityRepository;
use Doctrine\ORM\EntityManagerInterface;

final class RegisterUser
{
    public function __construct(
        private readonly IdentityRepository $identityRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly PasswordHasher $passwordHasher
    ) {
    }

    /**
     * @return array{user_id:int, identity_id:int, provider:string}
     */
    public function register(RegisterRequest $request): array
    {
        [$provider, $identifier] = $this->resolveIdentifier($request);

        $existing = $this->identityRepository->findOneBy([
            'provider' => $provider,
            'identifier' => $identifier,
        ]);

        if ($existing !== null) {
            throw new IdentityAlreadyExists('Identity already exists.');
        }

        $user = new User();
        $identity = new Identity($provider, $identifier);
        $identity->setPasswordHash($this->passwordHasher->hash($request->password ?? ''));
        $user->addIdentity($identity);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return [
            'user_id' => $user->getId() ?? 0,
            'identity_id' => $identity->getId() ?? 0,
            'provider' => $provider,
        ];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolveIdentifier(RegisterRequest $request): array
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
