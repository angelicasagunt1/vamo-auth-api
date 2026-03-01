<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Doctrine\Repository;

use App\Auth\Domain\Entity\Identity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class IdentityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Identity::class);
    }
}
