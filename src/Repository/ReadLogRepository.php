<?php

namespace App\Repository;

use App\Entity\ReadLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * @return ReadLog[]
     */
    public function findByReaderAndTitle(int $readerId, string $title = '', ?int $limit = null, ?int $offset = null): array
    {
        return $this->createReaderAndTitleQuery($readerId, $title)
            ->addSelect('b')
            ->orderBy('r.startDate', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset ?? 0)
            ->getQuery()
            ->getResult();
    }

    public function countByReaderAndTitle(int $readerId, string $title = ''): int
    {
        return (int) $this->createReaderAndTitleQuery($readerId, $title)
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createReaderAndTitleQuery(int $readerId, string $title): QueryBuilder
    {
        $query = $this->createQueryBuilder('r')
            ->leftJoin('r.book', 'b')
            ->where('r.reader = :readerId')
            ->setParameter('readerId', $readerId);

        if ($title !== '') {
            $query->andWhere('LOWER(b.title) LIKE LOWER(:title)')
                ->setParameter('title', '%' . addcslashes($title, '%_') . '%');
        }

        return $query;
    }
}
