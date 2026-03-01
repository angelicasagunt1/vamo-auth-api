<?php

declare(strict_types=1);

namespace App\Auth\Application\Security;

use App\Auth\Domain\Entity\Identity;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class PasswordHasher
{
    public function __construct(private readonly PasswordHasherFactoryInterface $hasherFactory)
    {
    }

    public function hash(string $plainPassword): string
    {
        $hasher = $this->hasherFactory->getPasswordHasher(Identity::class);

        return $hasher->hash($plainPassword);
    }

    public function verify(?string $hashedPassword, string $plainPassword): bool
    {
        if ($hashedPassword === null) {
            return false;
        }

        $hasher = $this->hasherFactory->getPasswordHasher(Identity::class);

        return $hasher->verify($hashedPassword, $plainPassword);
    }
}
