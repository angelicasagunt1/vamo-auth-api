<?php

declare(strict_types=1);

namespace App\Auth\Domain\Entity;

use App\Auth\Infrastructure\Doctrine\Repository\OtpCodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OtpCodeRepository::class)]
#[ORM\Table(name: 'otp_codes')]
#[ORM\Index(name: 'idx_otp_phone', columns: ['phone'])]
#[ORM\HasLifecycleCallbacks]
class OtpCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $phone;

    #[ORM\Column(type: 'string', length: 64)]
    private string $codeHash;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'smallint')]
    private int $attempts = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $phone, string $codeHash, \DateTimeImmutable $expiresAt)
    {
        $this->phone = $phone;
        $this->codeHash = $codeHash;
        $this->expiresAt = $expiresAt;
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getCodeHash(): string
    {
        return $this->codeHash;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function incrementAttempts(): void
    {
        $this->attempts++;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
