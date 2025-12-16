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

    public function findByYear(int $year, int $readerId)
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.reader', 'p')
            ->where('r.finishDate >= :initialDate AND r.finishDate <= :endDate')
            ->andWhere('p.id = :readerId')
            ->setParameter('initialDate', "$year-01-01")
            ->setParameter('endDate', "$year-12-31")
            ->setParameter('readerId', $readerId)
            ->orderBy('r.finishDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUnfinished(int $readerId)
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.reader', 'p')
            ->where('r.finishDate is null')
            ->andWhere('p.id = :readerId')
            ->setParameter('readerId', $readerId)
            ->getQuery()->getResult();
    }
}
