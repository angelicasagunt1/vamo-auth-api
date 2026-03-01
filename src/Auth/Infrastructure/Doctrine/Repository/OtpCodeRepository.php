<?php

declare(strict_types=1);

namespace App\Auth\Infrastructure\Doctrine\Repository;

use App\Auth\Domain\Entity\OtpCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class OtpCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OtpCode::class);
    }
}
