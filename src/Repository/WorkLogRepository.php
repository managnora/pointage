<?php

namespace App\Repository;

use App\Entity\WorkLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WorkLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkLog[]    findAll()
 * @method WorkLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkLog::class);
    }

    public function findOneByDate(\DateTimeInterface $date): ?WorkLog
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.date = :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return WorkLog[] */
    public function findByMonth(int $year, int $month): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('YEAR(w.date) = :year')
            ->andWhere('MONTH(w.date) = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->orderBy('w.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getAvailableMonths(): array
    {
        return $this->createQueryBuilder('w')
            ->select('DISTINCT YEAR(w.date) as year, MONTH(w.date) as month')
            ->orderBy('year', 'ASC')
            ->addOrderBy('month', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
