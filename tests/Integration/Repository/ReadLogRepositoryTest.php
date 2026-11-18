<?php

namespace App\Tests\Integration\Repository;

use App\DataFixtures\BookFixtures;
use App\DataFixtures\PersonFixtures;
use App\DataFixtures\ReadLogFixtures;
use App\Entity\Book;
use App\Entity\Person;
use App\Entity\ReadLog;
use App\Repository\ReadLogRepository;
use App\Tests\Integration\DatabaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ReadLogRepositoryTest extends DatabaseTestCase
{
    private ReadLogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->entityManager->getRepository(ReadLog::class);
    }

    #[DataProvider('findByYearProvider')]
    public function testFindByYearReturnsLogsFinishedThatYearOrderedByFinishDate(string $reader, int $year, array $expectedLogs): void
    {
        $logs = $this->repository->findByYear($year, $this->id($reader));

        self::assertSame($this->ids($expectedLogs), $this->logIds($logs));
    }

    public static function findByYearProvider(): iterable
    {
        yield 'alice 2024: two finished books, ordered by finish date' => [
            PersonFixtures::ALICE, 2024, [ReadLogFixtures::ALICE_DUNE, ReadLogFixtures::ALICE_HOBBIT_FIRST],
        ];
        yield 'alice 2025: the re-read and a same-day read count, the unfinished book does not' => [
            PersonFixtures::ALICE, 2025, [ReadLogFixtures::ALICE_HOBBIT_REREAD, ReadLogFixtures::ALICE_PROJECT_HAIL_MARY, ReadLogFixtures::ALICE_SNOW_CRASH],
        ];
        yield 'bob 2025: only his own finished log' => [
            PersonFixtures::BOB, 2025, [ReadLogFixtures::BOB_NEUROMANCER],
        ];
        yield 'bob 2024: nothing finished' => [
            PersonFixtures::BOB, 2024, [],
        ];
        yield 'year without any logs' => [
            PersonFixtures::ALICE, 2023, [],
        ];
        yield 'reader without any logs' => [
            PersonFixtures::CAROL, 2025, [],
        ];
    }

    public function testFindByYearIncludesFirstAndLastDayOfTheYearOnly(): void
    {
        $this->inRolledBackTransaction(function () {
            $alice = $this->entityManager->getReference(Person::class, $this->id(PersonFixtures::ALICE));
            $book = $this->entityManager->getReference(Book::class, $this->id(BookFixtures::KINDRED));

            $lastDayOf2023 = $this->createLog($alice, $book, '2023-12-31');
            $firstDayOf2026 = $this->createLog($alice, $book, '2026-01-01');
            $lastDayOf2026 = $this->createLog($alice, $book, '2026-12-31');
            $firstDayOf2027 = $this->createLog($alice, $book, '2027-01-01');

            self::assertSame(
                [$firstDayOf2026->getId(), $lastDayOf2026->getId()],
                $this->logIds($this->repository->findByYear(2026, $this->id(PersonFixtures::ALICE)))
            );
            self::assertContains($lastDayOf2023->getId(), $this->logIds($this->repository->findByYear(2023, $this->id(PersonFixtures::ALICE))));
            self::assertContains($firstDayOf2027->getId(), $this->logIds($this->repository->findByYear(2027, $this->id(PersonFixtures::ALICE))));
        });
    }

    #[DataProvider('searchProvider')]
    public function testFindByReaderAndTitle(string $reader, string $title, array $expectedLogs): void
    {
        $logs = $this->repository->findByReaderAndTitle($this->id($reader), $title);

        self::assertSame($this->ids($expectedLogs), $this->logIds($logs));
    }

    #[DataProvider('searchProvider')]
    public function testCountByReaderAndTitleMatchesFind(string $reader, string $title, array $expectedLogs): void
    {
        self::assertSame(count($expectedLogs), $this->repository->countByReaderAndTitle($this->id($reader), $title));
    }

    public static function searchProvider(): iterable
    {
        $allAlice = [
            ReadLogFixtures::ALICE_DUNE,
            ReadLogFixtures::ALICE_HOBBIT_FIRST,
            ReadLogFixtures::ALICE_HOBBIT_REREAD,
            ReadLogFixtures::ALICE_PROJECT_HAIL_MARY,
            ReadLogFixtures::ALICE_LOTR,
            ReadLogFixtures::ALICE_SNOW_CRASH,
        ];

        yield 'no title: all logs of the reader, ordered by start date' => [PersonFixtures::ALICE, '', $allAlice];
        yield 'no title: other reader only gets their own logs' => [
            PersonFixtures::BOB, '', [ReadLogFixtures::BOB_NEUROMANCER, ReadLogFixtures::BOB_FOUNDATION],
        ];
        yield 'no title: reader without logs gets nothing' => [PersonFixtures::CAROL, '', []];
        yield 'exact title' => [PersonFixtures::ALICE, 'Dune', [ReadLogFixtures::ALICE_DUNE]];
        yield 'case insensitive' => [
            PersonFixtures::ALICE, 'hOBBIT', [ReadLogFixtures::ALICE_HOBBIT_FIRST, ReadLogFixtures::ALICE_HOBBIT_REREAD],
        ];
        yield 'partial match in several titles' => [
            PersonFixtures::ALICE, 'the', [ReadLogFixtures::ALICE_HOBBIT_FIRST, ReadLogFixtures::ALICE_HOBBIT_REREAD, ReadLogFixtures::ALICE_LOTR],
        ];
        yield 'book read by another reader is not found' => [PersonFixtures::ALICE, 'Neuromancer', []];
        yield 'no match' => [PersonFixtures::ALICE, 'Moby Dick', []];
        yield '% is matched literally, not as a wildcard' => [PersonFixtures::ALICE, '%', []];
        yield '_ is matched literally, not as a wildcard' => [PersonFixtures::ALICE, 'D_ne', []];
    }

    public function testFindByReaderAndTitlePaginates(): void
    {
        $alice = $this->id(PersonFixtures::ALICE);

        self::assertSame(
            $this->ids([ReadLogFixtures::ALICE_DUNE, ReadLogFixtures::ALICE_HOBBIT_FIRST]),
            $this->logIds($this->repository->findByReaderAndTitle($alice, '', 2, 0))
        );
        self::assertSame(
            $this->ids([ReadLogFixtures::ALICE_HOBBIT_REREAD, ReadLogFixtures::ALICE_PROJECT_HAIL_MARY]),
            $this->logIds($this->repository->findByReaderAndTitle($alice, '', 2, 2))
        );
        self::assertSame(
            $this->ids([ReadLogFixtures::ALICE_LOTR, ReadLogFixtures::ALICE_SNOW_CRASH]),
            $this->logIds($this->repository->findByReaderAndTitle($alice, '', 2, 4))
        );
        self::assertSame([], $this->repository->findByReaderAndTitle($alice, '', 2, 6));
    }

    public function testFindByReaderAndTitlePaginatesFilteredResults(): void
    {
        $alice = $this->id(PersonFixtures::ALICE);

        self::assertSame(
            $this->ids([ReadLogFixtures::ALICE_HOBBIT_REREAD]),
            $this->logIds($this->repository->findByReaderAndTitle($alice, 'hobbit', 1, 1))
        );
        self::assertSame(2, $this->repository->countByReaderAndTitle($alice, 'hobbit'));
    }

    public function testLogsWithTheSameStartDateHaveAStableOrder(): void
    {
        $this->inRolledBackTransaction(function () {
            $bob = $this->entityManager->getReference(Person::class, $this->id(PersonFixtures::BOB));
            $book = $this->entityManager->getReference(Book::class, $this->id(BookFixtures::NEUROMANCER));

            $first = $this->createLog($bob, $book, null, '2020-01-01');
            $second = $this->createLog($bob, $book, null, '2020-01-01');

            $page1 = $this->repository->findByReaderAndTitle($this->id(PersonFixtures::BOB), 'Neuromancer', 1, 0);
            $page2 = $this->repository->findByReaderAndTitle($this->id(PersonFixtures::BOB), 'Neuromancer', 1, 1);

            self::assertSame([$first->getId()], $this->logIds($page1));
            self::assertSame([$second->getId()], $this->logIds($page2));
        });
    }

    public function testFindByReaderAndTitleLoadsTheBookWithTheLog(): void
    {
        $logs = $this->repository->findByReaderAndTitle($this->id(PersonFixtures::ALICE), 'Dune');

        self::assertCount(1, $logs);
        self::assertSame('Dune', $logs[0]->getBook()->getTitle());
        self::assertSame('Frank Herbert', $logs[0]->getBook()->getAuthor());
    }

    public function testUnknownReaderHasNoLogs(): void
    {
        self::assertSame([], $this->repository->findByReaderAndTitle(-1));
        self::assertSame(0, $this->repository->countByReaderAndTitle(-1));
        self::assertSame([], $this->repository->findByYear(2025, -1));
    }

    private function ids(array $references): array
    {
        return array_map(fn (string $reference) => $this->id($reference), $references);
    }

    private function logIds(array $logs): array
    {
        return array_map(fn (ReadLog $log) => $log->getId(), $logs);
    }

    private function createLog(Person $reader, Book $book, ?string $finishDate, ?string $startDate = null): ReadLog
    {
        $log = new ReadLog();
        $log->setReader($reader);
        $log->setBook($book);
        $log->setStartDate(new \DateTime($startDate ?? $finishDate));
        $log->setFinishDate($finishDate ? new \DateTime($finishDate) : null);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $log;
    }
}
