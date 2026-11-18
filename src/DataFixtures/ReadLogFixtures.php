<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Person;
use App\Entity\ReadLog;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Reading logs used by the tests (see BookFixtures for the resulting statuses).
 *
 * | Log                      | Reader | Book              | Start      | Finish     | Days | Rating |
 * |--------------------------|--------|-------------------|------------|------------|------|--------|
 * | ALICE_DUNE               | alice  | Dune              | 2024-02-01 | 2024-03-10 | 38   | 5      |
 * | ALICE_HOBBIT_FIRST       | alice  | The Hobbit        | 2024-07-01 | 2024-07-12 | 11   | 4      |
 * | ALICE_HOBBIT_REREAD      | alice  | The Hobbit        | 2025-01-05 | 2025-01-15 | 10   | 5      |
 * | ALICE_PROJECT_HAIL_MARY  | alice  | Project Hail Mary | 2025-02-10 | 2025-02-10 | 0    | 4      |
 * | ALICE_LOTR               | alice  | The Lord of ...   | 2025-03-01 | -          |      |        |
 * | ALICE_SNOW_CRASH         | alice  | Snow Crash        | 2025-06-01 | 2025-06-22 | 21   | 3      |
 * | BOB_NEUROMANCER          | bob    | Neuromancer       | 2025-04-01 | 2025-04-20 | 19   | 3      |
 * | BOB_FOUNDATION           | bob    | Foundation        | 2025-05-02 | -          |      |        |
 *
 * Finished in 2025 (used by the dashboard's "this year" statistics):
 * - alice: The Hobbit (re-read), Project Hail Mary (no page count, same day), Snow Crash
 *          = 3 books, 780 pages, (10 + 0 + 21) / 3 = 10.33 days on average
 * - bob:   Neuromancer = 1 book, 271 pages, 19 days
 * - carol: nothing
 */
class ReadLogFixtures extends Fixture implements DependentFixtureInterface
{
    public const string ALICE_DUNE = 'log-alice-dune';
    public const string ALICE_HOBBIT_FIRST = 'log-alice-hobbit-first';
    public const string ALICE_HOBBIT_REREAD = 'log-alice-hobbit-reread';
    public const string ALICE_LOTR = 'log-alice-lotr';
    public const string ALICE_PROJECT_HAIL_MARY = 'log-alice-project-hail-mary';
    public const string ALICE_SNOW_CRASH = 'log-alice-snow-crash';
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
            self::ALICE_PROJECT_HAIL_MARY => [PersonFixtures::ALICE, BookFixtures::PROJECT_HAIL_MARY, '2025-02-10', '2025-02-10', 4, 'Read it in one sitting.'],
            self::ALICE_SNOW_CRASH => [PersonFixtures::ALICE, BookFixtures::SNOW_CRASH, '2025-06-01', '2025-06-22', 3, null],
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
