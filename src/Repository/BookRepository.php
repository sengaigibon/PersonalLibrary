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
    public function findBySearchCriteria(string $title = '', string $author = '', string $status = '', int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('b');

        $this->applyConditions($title, $qb, $author, $status);

        return $qb->orderBy('b.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count books by search criteria
     */
    public function countBySearchCriteria(string $title = '', string $author = '', string $status = ''): int
    {
        $qb = $this->createQueryBuilder('b')
                   ->select('COUNT(b.id)');

        $this->applyConditions($title, $qb, $author, $status);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function applyConditions(string $title, QueryBuilder $qb, string $author, string $status): void
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
                    ->where('rl.finishDate IS NOT NULL');
            } else {
                $qb->leftJoin('b.readLogs', 'rl')
                    ->where('rl.finishDate IS NULL OR rl.id IS NULL');
            }
        }
    }
}
