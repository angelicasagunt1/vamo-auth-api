<?php

declare(strict_types=1);

namespace App\Auth\Application\Service;

use App\Auth\Domain\Entity\Identity;
use App\Auth\Domain\Entity\RefreshToken;
use App\Auth\Domain\Entity\User;
use App\Auth\Infrastructure\Doctrine\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

final class RefreshTokenService
{
    private const REFRESH_TTL = 'P30D';

    public function __construct(
        private readonly RefreshTokenRepository $repository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function issue(User $user, ?Identity $identity): string
    {
        $token = $this->generateToken();
        $hash = $this->hashToken($token);
        $expiresAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->add(new \DateInterval(self::REFRESH_TTL));

        $refreshToken = new RefreshToken($user, $identity, $hash, $expiresAt);
        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        return $token;
    }

    /**
     * @return array{token:string, user:User, identity:?Identity}
     */
    public function rotate(string $token): array
    {
        $hash = $this->hashToken($token);
        $refreshToken = $this->repository->findOneBy(['tokenHash' => $hash]);

        if ($refreshToken === null || $refreshToken->getRevokedAt() !== null) {
            throw new \RuntimeException('Invalid refresh token.');
        }

        if ($refreshToken->isExpired()) {
            $refreshToken->revoke();
            $this->entityManager->flush();
            throw new \RuntimeException('Refresh token expired.');
        }

        $refreshToken->revoke();
        $newToken = $this->issue($refreshToken->getUser(), $refreshToken->getIdentity());

        return [
            'token' => $newToken,
            'user' => $refreshToken->getUser(),
            'identity' => $refreshToken->getIdentity(),
        ];
    }

    private function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
