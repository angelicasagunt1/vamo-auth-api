<?php

declare(strict_types=1);

namespace App\Auth\Application\Service;

use App\Auth\Application\Dto\ProviderLoginRequest;
use App\Auth\Application\Provider\AuthProviderRegistry;

final class LoginWithProvider
{
    public function __construct(private readonly AuthProviderRegistry $registry)
    {
    }

    /**
     * @return array{token:string}
     */
    public function login(ProviderLoginRequest $request): array
    {
        $provider = $request->provider ?? '';
        $authProvider = $this->registry->get($provider);

        return $authProvider->authenticate($request);
    }
}
