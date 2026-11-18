<?php

namespace App\DataFixtures;

use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Books used by the integration tests. Their status per reader comes from ReadLogFixtures:
 *
 * | Book            | alice               | bob      |
 * |-----------------|---------------------|----------|
 * | Dune            | FINISHED            | UNREAD   |
 * | The Hobbit      | FINISHED (2 times)  | UNREAD   |
 * | The Lord of ... | READING             | UNREAD   |
 * | Neuromancer     | UNREAD              | FINISHED |
 * | Foundation      | UNREAD              | READING  |
 * | Clean Code      | UNREAD (reference)  | UNREAD   |
 */
class BookFixtures extends Fixture
{
    public const string DUNE = 'book-dune';
    public const string HOBBIT = 'book-hobbit';
    public const string LOTR = 'book-lotr';
    public const string NEUROMANCER = 'book-neuromancer';
    public const string FOUNDATION = 'book-foundation';
    public const string CLEAN_CODE = 'book-clean-code';

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
