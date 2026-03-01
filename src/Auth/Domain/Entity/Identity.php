<?php

declare(strict_types=1);

namespace App\Auth\Domain\Entity;

use App\Auth\Infrastructure\Doctrine\Repository\IdentityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IdentityRepository::class)]
#[ORM\Table(name: 'user_identities')]
#[ORM\Index(name: 'idx_identity_user', columns: ['user_id'])]
#[ORM\UniqueConstraint(name: 'uniq_identity_provider_identifier', columns: ['provider', 'identifier'])]
#[ORM\HasLifecycleCallbacks]
class Identity
{
    public const PROVIDER_EMAIL = 'email';
    public const PROVIDER_PHONE = 'phone';
    public const PROVIDER_GOOGLE = 'google';
    public const PROVIDER_APPLE = 'apple';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'identities')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 32)]
    private string $provider;

    #[ORM\Column(type: 'string', length: 255)]
    private string $identifier;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $passwordHash = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $provider, string $identifier)
    {
        $this->provider = $provider;
        $this->identifier = $identifier;
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(?string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function markVerified(): void
    {
        $this->verifiedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
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
