<?php

declare(strict_types=1);

namespace App\Auth\Application\Provider;

use App\Auth\Application\Dto\ProviderLoginRequest;
use App\Auth\Application\Exception\ProviderNotConfigured;
use App\Auth\Domain\Entity\Identity;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class GoogleAuthProvider implements AuthProviderInterface
{
    public function __construct(
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

        throw new ProviderNotConfigured('Google auth not implemented yet.');
    }
}
