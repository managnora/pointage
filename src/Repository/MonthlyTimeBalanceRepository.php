<?php

namespace App\Repository;

use App\Entity\MonthlyTimeBalance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MonthlyTimeBalance|null find($id, $lockMode = null, $lockVersion = null)
 * @method MonthlyTimeBalance|null findOneBy(array $criteria, array $orderBy = null)
 * @method MonthlyTimeBalance[]    findAll()
 * @method MonthlyTimeBalance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MonthlyTimeBalanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MonthlyTimeBalance::class);
    }
}
