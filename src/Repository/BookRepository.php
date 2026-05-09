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
        $qb = $this->createQueryBuilder('book');

        $this->applyConditions($qb, $readerId, $title, $author, $status);

        return $qb->orderBy('book.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findBooksBroughtInYear(int $year, int $readerId): array
    {
        return $this->createQueryBuilder('book')
            ->leftJoin('book.readLogs', 'log')
            ->leftJoin('log.reader', 'reader')
            ->where('book.purchaseDate >= :startDate')
            ->andWhere('book.purchaseDate < :endDate')
            ->andWhere('reader.id = :readerId OR reader.id IS NULL')
            ->andWhere('log.finishDate IS NULL OR log.id IS NULL')
            ->setParameter('startDate', new \DateTime($year . '-01-01'))
            ->setParameter('endDate', new \DateTime(($year + 1) . '-01-01'))
            ->setParameter('readerId', $readerId)
            ->orderBy('book.purchaseDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count books by search criteria
     */
    public function countBySearchCriteria(int $readerId, string $title = '', string $author = '', string $status = ''): int
    {
        $qb = $this->createQueryBuilder('book')
                   ->select('COUNT(book.id)');

        $this->applyConditions($qb, $readerId, $title, $author, $status);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function applyConditions(QueryBuilder $qb, int $readerId, string $title, string $author, string $status): void
    {
        if (!empty($title)) {
            $qb->andWhere('LOWER(book.title) LIKE LOWER(:title)')
                ->setParameter('title', '%' . $title . '%');
        }

        if (!empty($author)) {
            $qb->andWhere('LOWER(book.author) LIKE LOWER(:author)')
                ->setParameter('author', '%' . $author . '%');
        }

        if (!empty($status)) {
            switch ($status) {
                case Book::STATUS_FINISHED:
                    $qb->innerJoin('book.readLogs', 'log')
                        ->innerJoin('log.reader', 'reader')
                        ->where('reader.id = :readerId')
                        ->andWhere('log.finishDate IS NOT NULL')
                        ->setParameter('readerId', $readerId);
                    break;

                case Book::STATUS_READING:
                    $qb->leftJoin('book.readLogs', 'log')
                        ->innerJoin('log.reader', 'reader')
                        ->where('reader.id = :readerId')
                        ->andWhere('log.finishDate IS NULL OR log.id IS NULL')
                        ->setParameter('readerId', $readerId);
                    break;

                case Book::STATUS_UNREAD:
                default:
                    $qb->leftJoin('book.readLogs', 'log')
                        ->leftJoin('log.reader', 'reader')
                        ->andWhere('reader.id = :readerId OR reader.id IS NULL')
                        ->andWhere('log.finishDate IS NULL OR log.id IS NULL')
                        ->setParameter('readerId', $readerId);
                    break;
            }
        }
    }
}
