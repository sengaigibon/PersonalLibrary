<?php

namespace App\DataFixtures;

use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Books used by the tests. Their status per reader comes from ReadLogFixtures
 * (carol has no reading logs, so every book is UNREAD for her):
 *
 * | Book              | Bought     | Pages | alice              | bob      |
 * |-------------------|------------|-------|--------------------|----------|
 * | Dune              | 2023-03-15 | 688   | FINISHED           | UNREAD   |
 * | The Hobbit        | 2022-11-02 | 300   | FINISHED (2 times) | UNREAD   |
 * | The Lord of ...   | 2024-01-20 | 1178  | READING            | UNREAD   |
 * | Neuromancer       | 2024-06-10 | 271   | UNREAD             | FINISHED |
 * | Foundation        | 2025-02-01 | 255   | UNREAD             | READING  |
 * | Clean Code        | -          | 464   | UNREAD (reference) | UNREAD   |
 * | Project Hail Mary | 2024-09-05 | -     | FINISHED           | UNREAD   |
 * | Solaris           | 2024-11-20 | 204   | UNREAD             | UNREAD   |
 * | Snow Crash        | 2025-03-10 | 480   | FINISHED           | UNREAD   |
 * | Kindred           | 2025-07-15 | 264   | UNREAD             | UNREAD   |
 *
 * Bought per year (used by the dashboard):
 * - 2024: The Lord of the Rings, Neuromancer, Project Hail Mary, Solaris
 * - 2025: Foundation, Snow Crash, Kindred
 */
class BookFixtures extends Fixture
{
    public const string DUNE = 'book-dune';
    public const string HOBBIT = 'book-hobbit';
    public const string LOTR = 'book-lotr';
    public const string NEUROMANCER = 'book-neuromancer';
    public const string FOUNDATION = 'book-foundation';
    public const string CLEAN_CODE = 'book-clean-code';
    public const string PROJECT_HAIL_MARY = 'book-project-hail-mary';
    public const string SOLARIS = 'book-solaris';
    public const string SNOW_CRASH = 'book-snow-crash';
    public const string KINDRED = 'book-kindred';

    public function load(ObjectManager $manager): void
    {
        $books = [
            // reference => [title, author, isbn, pages, purchaseDate, isReference]
            self::DUNE => ['Dune', 'Frank Herbert', '9780441172719', 688, '2023-03-15', false],
            self::HOBBIT => ['The Hobbit', 'J.R.R. Tolkien', '9780547928227', 300, '2022-11-02', false],
            self::LOTR => ['The Lord of the Rings', 'J.R.R. Tolkien', '9780544003415', 1178, '2024-01-20', false],
            self::NEUROMANCER => ['Neuromancer', 'William Gibson', '9780441569595', 271, '2024-06-10', false],
            self::FOUNDATION => ['Foundation', 'Isaac Asimov', '9780553293357', 255, '2025-02-01', false],
            self::CLEAN_CODE => ['Clean Code', 'Robert C. Martin', '9780132350884', 464, null, true],
            self::PROJECT_HAIL_MARY => ['Project Hail Mary', 'Andy Weir', '9780593135204', null, '2024-09-05', false],
            self::SOLARIS => ['Solaris', 'Stanisław Lem', '9780156837507', 204, '2024-11-20', false],
            self::SNOW_CRASH => ['Snow Crash', 'Neal Stephenson', '9780553380958', 480, '2025-03-10', false],
            self::KINDRED => ['Kindred', 'Octavia E. Butler', '9780807083697', 264, '2025-07-15', false],
        ];

        foreach ($books as $reference => [$title, $author, $isbn, $pages, $purchaseDate, $isReference]) {
            $book = new Book();
            $book->setTitle($title);
            $book->setAuthor($author);
            $book->setIsbn($isbn);
            $book->setPages($pages);
            $book->setPurchaseDate($purchaseDate ? new \DateTime($purchaseDate) : null);
            $book->setIsReference($isReference);

            $manager->persist($book);
            $this->addReference($reference, $book);
        }

        $manager->flush();
    }
}
