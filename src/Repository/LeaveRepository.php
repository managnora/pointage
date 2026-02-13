<?php

namespace App\Repository;

use App\Entity\Leave;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Leave|null find($id, $lockMode = null, $lockVersion = null)
 * @method Leave|null findOneBy(array $criteria, array $orderBy = null)
 * @method Leave[]    findAll()
 * @method Leave[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LeaveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Leave::class);
    }
}
