<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Person;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BookFixtures extends Fixture implements DependentFixtureInterface
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
            self::DUNE => [PersonFixtures::ALICE, 'Dune', 'Frank Herbert', '9780441172719', 688, '2023-03-15', false],
            self::HOBBIT => [PersonFixtures::ALICE, 'The Hobbit', 'J.R.R. Tolkien', '9780547928227', 300, '2022-11-02', false],
            self::LOTR => [PersonFixtures::ALICE, 'The Lord of the Rings', 'J.R.R. Tolkien', '9780544003415', 1178, '2024-01-20', false],
            self::NEUROMANCER => [PersonFixtures::BOB, 'Neuromancer', 'William Gibson', '9780441569595', 271, '2024-06-10', false],
            self::FOUNDATION => [PersonFixtures::BOB, 'Foundation', 'Isaac Asimov', '9780553293357', 255, '2025-02-01', false],
            self::CLEAN_CODE => [PersonFixtures::ALICE, 'Clean Code', 'Robert C. Martin', '9780132350884', 464, null, true],
            self::PROJECT_HAIL_MARY => [PersonFixtures::ALICE, 'Project Hail Mary', 'Andy Weir', '9780593135204', null, '2024-09-05', false],
            self::SOLARIS => [PersonFixtures::ALICE, 'Solaris', 'Stanisław Lem', '9780156837507', 204, '2024-11-20', false],
            self::SNOW_CRASH => [PersonFixtures::ALICE, 'Snow Crash', 'Neal Stephenson', '9780553380958', 480, '2025-03-10', false],
            self::KINDRED => [PersonFixtures::ALICE, 'Kindred', 'Octavia E. Butler', '9780807083697', 264, '2025-07-15', false],
        ];

        foreach ($books as $reference => [$owner, $title, $author, $isbn, $pages, $purchaseDate, $isReference]) {
            $book = new Book();
            $book->setOwner($this->getReference($owner, Person::class));
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

    public function getDependencies(): array
    {
        return [PersonFixtures::class];
    }
}
