<?php

declare(strict_types=1);

namespace App\Auth\Application\Provider;

use App\Auth\Application\Exception\ProviderNotSupported;

final class AuthProviderRegistry
{
    /**
     * @param iterable<AuthProviderInterface> $providers
     */
    public function __construct(private readonly iterable $providers)
    {
    }

    public function get(string $provider): AuthProviderInterface
    {
        foreach ($this->providers as $authProvider) {
            if ($authProvider->supports($provider)) {
                return $authProvider;
            }
        }

        throw new ProviderNotSupported('Provider not supported.');
    }
}
