<?php

declare(strict_types=1);

namespace App\Shared\Application;

final class HealthCheck
{
    public function status(): array
    {
        return [
            'status' => 'ok',
            'service' => 'auth',
            'timestamp' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
        ];
    }
}
