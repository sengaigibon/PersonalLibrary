<?php

namespace App\Repository;

use App\Entity\ReadLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReadLog>
 */
class ReadLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReadLog::class);
    }

    public function findByYear(int $year)
    {
        return $this->createQueryBuilder('r')
            ->where('r.finishDate >= :initialDate AND r.finishDate <= :endDate')
            ->setParameter('initialDate', "$year-01-01")
            ->setParameter('endDate', "$year-12-31")
            ->orderBy('r.finishDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUnfinished()
    {
        return $this->createQueryBuilder('r')
            ->where('r.finishDate is null')
            ->getQuery()->getResult();
    }
}
