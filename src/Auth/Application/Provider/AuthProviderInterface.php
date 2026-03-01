<?php

declare(strict_types=1);

namespace App\Auth\Application\Provider;

use App\Auth\Application\Dto\ProviderLoginRequest;

interface AuthProviderInterface
{
    public function supports(string $provider): bool;

    /**
     * @return array{token:string}
     */
    public function authenticate(ProviderLoginRequest $request): array;
}
