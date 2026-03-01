<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Controller;

use App\Shared\Application\HealthCheck;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    public function __construct(private readonly HealthCheck $healthCheck)
    {
    }

    #[Route(path: '/health', name: 'health_check', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->healthCheck->status());
    }
}
