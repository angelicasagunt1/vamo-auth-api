<?php

declare(strict_types=1);

namespace App\Auth\Application\Provider;

use App\Auth\Application\Dto\ProviderLoginRequest;
use App\Auth\Application\Exception\ProviderNotConfigured;
use App\Auth\Domain\Entity\Identity;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class AppleAuthProvider implements AuthProviderInterface
{
    public function __construct(
        #[Autowire('%env(APPLE_CLIENT_ID)%')]
        private readonly string $clientId,
        #[Autowire('%env(APPLE_TEAM_ID)%')]
        private readonly string $teamId,
        #[Autowire('%env(APPLE_KEY_ID)%')]
        private readonly string $keyId,
        #[Autowire('%env(APPLE_PRIVATE_KEY_PATH)%')]
        private readonly string $privateKeyPath
    ) {
    }

    public function supports(string $provider): bool
    {
        return $provider === Identity::PROVIDER_APPLE;
    }

    public function authenticate(ProviderLoginRequest $request): array
    {
        if ($this->clientId === '' || $this->teamId === '' || $this->keyId === '' || $this->privateKeyPath === '') {
            throw new ProviderNotConfigured('Apple provider is not configured.');
        }

        throw new ProviderNotConfigured('Apple auth not implemented yet.');
    }
}
