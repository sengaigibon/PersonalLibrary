<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * Find books by search criteria with pagination
     */
    public function findBySearchCriteria(int $readerId, string $title = '', string $author = '', string $status = '', int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('b');

        $this->applyConditions($qb, $readerId, $title, $author, $status);

        return $qb->orderBy('b.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findBooksBoughtInYear(int $year): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.purchaseDate >= :startDate')
            ->andWhere('b.purchaseDate < :endDate')
            ->setParameter('startDate', new \DateTime($year . '-01-01'))
            ->setParameter('endDate', new \DateTime(($year + 1) . '-01-01'))
            ->orderBy('b.purchaseDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count books by search criteria
     */
    public function countBySearchCriteria(int $readerId, string $title = '', string $author = '', string $status = ''): int
    {
        $qb = $this->createQueryBuilder('b')
                   ->select('COUNT(b.id)');

        $this->applyConditions($qb, $readerId, $title, $author, $status);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function applyConditions(QueryBuilder $qb, int $readerId, string $title, string $author, string $status): void
    {
        if (!empty($title)) {
            $qb->andWhere('LOWER(b.title) LIKE LOWER(:title)')
                ->setParameter('title', '%' . $title . '%');
        }

        if (!empty($author)) {
            $qb->andWhere('LOWER(b.author) LIKE LOWER(:author)')
                ->setParameter('author', '%' . $author . '%');
        }

        if (!empty($status)) {
            if ($status === Book::STATUS_FINISHED) {
                $qb->innerJoin('b.readLogs', 'rl')
                    ->innerJoin('rl.reader', 'r')
                    ->where('r.id = :readerId')
                    ->andWhere('rl.finishDate IS NOT NULL')
                    ->setParameter('readerId', $readerId);
            } else {
                $qb->leftJoin('b.readLogs', 'rl')
                    ->innerJoin('rl.reader', 'r')
                    ->where('r.id = :readerId')
                    ->andWhere('rl.finishDate IS NULL OR rl.id IS NULL')
                    ->setParameter('readerId', $readerId);
            }
        }
    }
}
