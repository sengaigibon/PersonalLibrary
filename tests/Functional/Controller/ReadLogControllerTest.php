<?php

namespace App\Tests\Functional\Controller;

use App\DataFixtures\BookFixtures;
use App\DataFixtures\PersonFixtures;
use App\DataFixtures\ReadLogFixtures;
use App\Entity\ReadLog;
use App\Tests\Functional\ApplicationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

final class ReadLogControllerTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->chooseReader($this->id(PersonFixtures::ALICE));
    }

    public function testReadingLogListsOnlyTheReadersLogs(): void
    {
        $this->chooseReader($this->id(PersonFixtures::BOB));

        $this->client->request('GET', '/read/log');

        self::assertResponseIsSuccessful();
        self::assertSame(
            ['Neuromancer', 'Foundation'],
            $this->client->getCrawler()->filter('table tbody tr td:first-child')->each(fn (Crawler $cell) => trim($cell->text()))
        );
        self::assertSelectorTextContains('.search-container', '2 entries found');
    }

    public function testNewFormOffersOnlyTheReadersBooksAndNoReaderField(): void
    {
        $this->client->request('GET', '/read/log/new');

        self::assertResponseIsSuccessful();
        self::assertSame(
            ['Clean Code', 'Dune', 'Kindred', 'Project Hail Mary', 'Snow Crash', 'Solaris', 'The Hobbit', 'The Lord of the Rings'],
            $this->client->getCrawler()->filter('select[name="read_log[book]"] option[value]:not([value=""])')->each(fn (Crawler $option) => $option->text())
        );
        self::assertSelectorNotExists('[name="read_log[reader]"]');
    }

    public function testNewCreatesALogForTheCurrentReader(): void
    {
        $this->client->request('GET', '/read/log/new');

        $this->client->submitForm('Save', [
            'read_log[book]' => (string) $this->id(BookFixtures::KINDRED),
            'read_log[startDate]' => '2025-08-01',
            'read_log[finishDate]' => '2025-08-10',
            'read_log[rating]' => '4',
        ]);

        self::assertResponseRedirects('/read/log?page=1&limit=20', 303);

        $logs = $this->entityManager()->getRepository(ReadLog::class)->findBy(['book' => $this->id(BookFixtures::KINDRED)]);
        self::assertCount(1, $logs);
        self::assertSame($this->id(PersonFixtures::ALICE), $logs[0]->getReader()->getId());
        self::assertSame('2025-08-10', $logs[0]->getFinishDate()->format('Y-m-d'));
        self::assertSame(4, $logs[0]->getRating());
    }

    public function testNewRejectsAnotherReadersBook(): void
    {
        $this->client->request('GET', '/read/log/new');
        $form = $this->client->getCrawler()->selectButton('Save')->form();
        $form->disableValidation();
        $form['read_log[book]']->setValue((string) $this->id(BookFixtures::NEUROMANCER));
        $form['read_log[startDate]']->setValue('2025-08-01');

        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(1, $this->entityManager()->getRepository(ReadLog::class)->count(['book' => $this->id(BookFixtures::NEUROMANCER)]));
    }

    public function testEditUpdatesTheLog(): void
    {
        $logId = $this->id(ReadLogFixtures::ALICE_DUNE);
        $this->client->request('GET', '/read/log/' . $logId . '/edit');

        $this->client->submitForm('Update Reading Log', [
            'read_log[rating]' => '3',
            'read_log[notes]' => 'Changed my mind.',
        ]);

        self::assertResponseRedirects('/read/log?page=1&limit=20', 303);

        $log = $this->entityManager()->find(ReadLog::class, $logId);
        self::assertSame(3, $log->getRating());
        self::assertSame('Changed my mind.', $log->getNotes());
        self::assertSame($this->id(PersonFixtures::ALICE), $log->getReader()->getId());
    }

    #[DataProvider('pagesOfAnotherReadersLogProvider')]
    public function testPagesOfAnotherReadersLogReturn404(string $method, string $path, array $parameters = []): void
    {
        $this->client->request($method, sprintf($path, $this->id(ReadLogFixtures::BOB_NEUROMANCER)), $parameters);

        self::assertResponseStatusCodeSame(404);
        self::assertNotNull($this->entityManager()->find(ReadLog::class, $this->id(ReadLogFixtures::BOB_NEUROMANCER)));
    }

    public static function pagesOfAnotherReadersLogProvider(): iterable
    {
        yield 'show' => ['GET', '/read/log/%d'];
        yield 'edit form' => ['GET', '/read/log/%d/edit'];
        yield 'edit submit' => ['POST', '/read/log/%d/edit', ['read_log' => ['rating' => '1']]];
        yield 'delete' => ['POST', '/read/log/%d', ['_token' => 'any']];
    }

    #[DataProvider('pagesWithoutAReaderProvider')]
    public function testPagesWithoutAReaderRedirectToTheWelcomePage(string $method, string $path): void
    {
        $this->leaveLibrary();

        $this->client->request($method, sprintf($path, $this->id(ReadLogFixtures::ALICE_DUNE)));

        self::assertResponseRedirects();
        self::assertNotNull($this->entityManager()->find(ReadLog::class, $this->id(ReadLogFixtures::ALICE_DUNE)));
    }

    public static function pagesWithoutAReaderProvider(): iterable
    {
        yield 'new' => ['GET', '/read/log/new'];
        yield 'show' => ['GET', '/read/log/%d'];
        yield 'edit' => ['GET', '/read/log/%d/edit'];
        yield 'delete' => ['POST', '/read/log/%d'];
    }

    public function testQuickNewCreatesALogForTheReadersBook(): void
    {
        $this->client->jsonRequest('POST', '/read/log/quicknew/' . $this->id(BookFixtures::KINDRED), [
            'startDate' => '2025-08-01',
            'endDate' => '2025-08-05',
            'rating' => 5,
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame(['status' => true], $this->jsonResponse());

        $log = $this->entityManager()->getRepository(ReadLog::class)->findOneBy(['book' => $this->id(BookFixtures::KINDRED)]);
        self::assertSame($this->id(PersonFixtures::ALICE), $log->getReader()->getId());
        self::assertSame('2025-08-05', $log->getFinishDate()->format('Y-m-d'));
    }

    public function testQuickNewRejectsAnotherReadersBook(): void
    {
        $this->client->jsonRequest('POST', '/read/log/quicknew/' . $this->id(BookFixtures::NEUROMANCER), [
            'startDate' => '2025-08-01',
            'endDate' => '2025-08-05',
        ]);

        self::assertResponseStatusCodeSame(404);
        self::assertSame(['status' => false, 'message' => 'Book not found'], $this->jsonResponse());
        self::assertSame(1, $this->entityManager()->getRepository(ReadLog::class)->count(['book' => $this->id(BookFixtures::NEUROMANCER)]));
    }
}
