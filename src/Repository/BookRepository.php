<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
    public function findBySearchCriteria(string $title = '', string $author = '', int $limit = 20, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('b');

        if (!empty($title)) {
            $qb->andWhere('LOWER(b.title) LIKE LOWER(:title)')
               ->setParameter('title', '%' . $title . '%');
        }

        if (!empty($author)) {
            $qb->andWhere('LOWER(b.author) LIKE LOWER(:author)')
               ->setParameter('author', '%' . $author . '%');
        }

        return $qb->orderBy('b.id', 'ASC')
                  ->setFirstResult($offset)
                  ->setMaxResults($limit)
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Count books by search criteria
     */
    public function countBySearchCriteria(string $title = '', string $author = ''): int
    {
        $qb = $this->createQueryBuilder('b')
                   ->select('COUNT(b.id)');

        if (!empty($title)) {
            $qb->andWhere('LOWER(b.title) LIKE LOWER(:title)')
               ->setParameter('title', '%' . $title . '%');
        }

        if (!empty($author)) {
            $qb->andWhere('LOWER(b.author) LIKE LOWER(:author)')
               ->setParameter('author', '%' . $author . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    //    /**
    //     * @return Book[] Returns an array of Book objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Book
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
