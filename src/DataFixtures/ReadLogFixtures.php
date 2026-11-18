<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Person;
use App\Entity\ReadLog;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Reading logs used by the integration tests (see BookFixtures for the resulting statuses).
 *
 * alice: 4 logs (3 finished, 1 in progress), finished 2 in 2024 and 1 in 2025.
 * bob:   2 logs (1 finished in 2025, 1 in progress).
 */
class ReadLogFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ALICE_DUNE = 'log-alice-dune';
    public const string ALICE_HOBBIT_FIRST = 'log-alice-hobbit-first';
    public const string ALICE_HOBBIT_REREAD = 'log-alice-hobbit-reread';
    public const string ALICE_LOTR = 'log-alice-lotr';
    public const string BOB_NEUROMANCER = 'log-bob-neuromancer';
    public const string BOB_FOUNDATION = 'log-bob-foundation';

    public function load(ObjectManager $manager): void
    {
        $logs = [
            // reference => [reader, book, startDate, finishDate, rating, notes]
            self::ALICE_DUNE => [PersonFixtures::ALICE, BookFixtures::DUNE, '2024-02-01', '2024-03-10', 5, 'A masterpiece.'],
            self::ALICE_HOBBIT_FIRST => [PersonFixtures::ALICE, BookFixtures::HOBBIT, '2024-07-01', '2024-07-12', 4, null],
            self::ALICE_HOBBIT_REREAD => [PersonFixtures::ALICE, BookFixtures::HOBBIT, '2025-01-05', '2025-01-15', 5, 'Even better the second time.'],
            self::ALICE_LOTR => [PersonFixtures::ALICE, BookFixtures::LOTR, '2025-03-01', null, null, null],
            self::BOB_NEUROMANCER => [PersonFixtures::BOB, BookFixtures::NEUROMANCER, '2025-04-01', '2025-04-20', 3, null],
            self::BOB_FOUNDATION => [PersonFixtures::BOB, BookFixtures::FOUNDATION, '2025-05-02', null, null, null],
        ];

        foreach ($logs as $reference => [$reader, $book, $startDate, $finishDate, $rating, $notes]) {
            $log = new ReadLog();
            $log->setReader($this->getReference($reader, Person::class));
            $log->setBook($this->getReference($book, Book::class));
            $log->setStartDate(new \DateTime($startDate));
            $log->setFinishDate($finishDate ? new \DateTime($finishDate) : null);
            $log->setRating($rating);
            $log->setNotes($notes);

            $manager->persist($log);
            $this->addReference($reference, $log);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [PersonFixtures::class, BookFixtures::class];
    }
}
