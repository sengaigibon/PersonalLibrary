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
        $initialDate = "$year-01-01";
        $endDate = "$year-12-31";
        $query = $this->createQueryBuilder('r')
            ->where('r.finishDate >= :initialDate AND r.finishDate <= :endDate')
            ->setParameter('initialDate', $initialDate)
            ->setParameter('endDate', $endDate)
            ->getQuery();

        return $query->getResult();
    }
    //    /**
    //     * @return ReadLog[] Returns an array of ReadLog objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ReadLog
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
