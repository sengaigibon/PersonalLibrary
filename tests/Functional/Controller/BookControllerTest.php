<?php

namespace App\Tests\Functional\Controller;

use App\DataFixtures\BookFixtures;
use App\DataFixtures\PersonFixtures;
use App\Entity\Book;
use App\Entity\ReadLog;
use App\Tests\Functional\ApplicationTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class BookControllerTest extends ApplicationTestCase
{
    public function testShowDisplaysTheBook(): void
    {
        $this->client->request('GET', '/books/' . $this->id(BookFixtures::DUNE));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.book-title', 'Dune');
        self::assertSelectorTextContains('.book-author', 'Frank Herbert');
        self::assertSelectorTextContains('.isbn-code', '9780441172719');
        self::assertSelectorTextContains('.pages-info', '688 pages');
        self::assertSelectorTextContains('.purchase-date', 'March 15, 2023');
    }

    public function testShowUnknownBookReturns404(): void
    {
        $this->client->request('GET', '/books/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testNewDisplaysTheForm(): void
    {
        $this->client->request('GET', '/books/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form input[name="book[title]"]');
        self::assertSelectorExists('form input[name="book[author]"]');
    }

    public function testNewCreatesTheBookAndRedirectsToTheLibraryKeepingPagination(): void
    {
        $this->chooseReader($this->id(PersonFixtures::ALICE));
        $this->client->request('GET', '/books/new?page=3&limit=10');

        $this->client->submitForm('Save', [
            'book[title]' => 'Hyperion',
            'book[author]' => 'Dan Simmons',
            'book[isbn]' => '9780553283686',
            'book[pages]' => '482',
            'book[purchaseDate]' => '2025-08-01',
        ]);

        self::assertResponseRedirects('/books?page=3&limit=10', 303);

        $book = $this->entityManager()->getRepository(Book::class)->findOneBy(['title' => 'Hyperion']);
        self::assertNotNull($book);
        self::assertSame('Dan Simmons', $book->getAuthor());
        self::assertSame('9780553283686', $book->getIsbn());
        self::assertSame(482, $book->getPages());
        self::assertSame('2025-08-01', $book->getPurchaseDate()?->format('Y-m-d'));
        self::assertFalse($book->isReference());

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Book created successfully!');
    }

    public function testNewRejectsADuplicateTitle(): void
    {
        $this->client->request('GET', '/books/new?page=2&limit=20');

        $this->client->submitForm('Save', [
            'book[title]' => 'Dune',
            'book[author]' => 'Someone Else',
        ]);

        self::assertResponseRedirects('/books/new?page=2&limit=20', 303);
        self::assertSame(1, $this->entityManager()->getRepository(Book::class)->count(['title' => 'Dune']));

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'Book already exists!!');
    }

    public function testNewWithMissingRequiredFieldsShowsTheFormAgain(): void
    {
        $booksBefore = $this->entityManager()->getRepository(Book::class)->count();
        $this->client->request('GET', '/books/new');

        $this->client->submitForm('Save', [
            'book[title]' => '',
            'book[author]' => '',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('form input[name="book[title]"]');
        self::assertSame($booksBefore, $this->entityManager()->getRepository(Book::class)->count());
    }

    public function testNewBackLinkKeepsPagination(): void
    {
        $this->client->request('GET', '/books/new?page=4&limit=50');

        self::assertSelectorExists('a.btn-back[href="/books?page=4&limit=50"]');
    }

    public function testEditUpdatesTheBookAndRedirectsToTheLibraryKeepingPagination(): void
    {
        $this->chooseReader($this->id(PersonFixtures::ALICE));
        $this->client->request('GET', '/books/' . $this->id(BookFixtures::DUNE) . '/edit?page=2&limit=50');

        self::assertResponseIsSuccessful();
        self::assertInputValueSame('book[title]', 'Dune');

        $this->client->submitForm('Update Book', [
            'book[title]' => 'Dune (Deluxe Edition)',
            'book[pages]' => '700',
        ]);

        self::assertResponseRedirects('/books?page=2&limit=50', 303);

        $book = $this->entityManager()->find(Book::class, $this->id(BookFixtures::DUNE));
        self::assertSame('Dune (Deluxe Edition)', $book->getTitle());
        self::assertSame(700, $book->getPages());
        self::assertSame('Frank Herbert', $book->getAuthor(), 'Fields not in the request keep their value');

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Book saved successfully!');
    }

    public function testEditUnknownBookReturns404(): void
    {
        $this->client->request('GET', '/books/999999/edit');

        self::assertResponseStatusCodeSame(404);
    }

    public function testDeleteRemovesTheBook(): void
    {
        $this->chooseReader($this->id(PersonFixtures::ALICE));
        $bookId = $this->id(BookFixtures::CLEAN_CODE);
        $this->client->request('GET', '/books/' . $bookId . '/edit');

        $this->client->submit($this->client->getCrawler()->filter('form[action="/books/' . $bookId . '"]')->form());

        self::assertResponseRedirects('/books', 303);
        self::assertNull($this->entityManager()->find(Book::class, $bookId));
    }

    public function testDeleteWithAnInvalidCsrfTokenKeepsTheBook(): void
    {
        $bookId = $this->id(BookFixtures::CLEAN_CODE);

        $this->client->request('POST', '/books/' . $bookId, ['_token' => 'invalid']);

        self::assertResponseRedirects('/books', 303);
        self::assertNotNull($this->entityManager()->find(Book::class, $bookId));
    }

    public function testQueryReturnsTitleAndPublishDateFromOpenLibrary(): void
    {
        $this->mockOpenLibrary(new MockResponse(json_encode([
            'ISBN:9780553283686' => ['title' => 'Hyperion', 'publish_date' => '1990', 'authors' => []],
        ])), $requestedUrl);

        $this->client->request('GET', '/books/query/9780553283686');

        self::assertResponseIsSuccessful();
        self::assertSame(['title' => 'Hyperion', 'publish_date' => '1990'], $this->jsonResponse());
        self::assertStringEndsWith('bibkeys=ISBN:9780553283686', $requestedUrl());
    }

    public function testQueryReturnsAnEmptyResultForAnUnknownIsbn(): void
    {
        $this->mockOpenLibrary(new MockResponse('{}'));

        $this->client->request('GET', '/books/query/0000000000');

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->jsonResponse());
    }

    public function testQueryReturnsAnEmptyResultWhenOpenLibraryFails(): void
    {
        $this->mockOpenLibrary(new MockResponse('Server error', ['http_code' => 500]));

        $this->client->request('GET', '/books/query/9780553283686');

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->jsonResponse());
    }

    public function testStartReadingWithoutAReaderReturnsAnError(): void
    {
        $this->client->request('GET', '/books/start/' . $this->id(BookFixtures::FOUNDATION));

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['error' => 'No reader has been set, go to the welcome page'], $this->jsonResponse());
        self::assertSame(0, $this->entityManager()->getRepository(ReadLog::class)->count(['book' => $this->id(BookFixtures::FOUNDATION), 'reader' => $this->id(PersonFixtures::ALICE)]));
    }

    public function testStartReadingCreatesALogForTheCurrentReaderStartingToday(): void
    {
        $this->chooseReader($this->id(PersonFixtures::ALICE));

        $this->client->request('GET', '/books/start/' . $this->id(BookFixtures::FOUNDATION));

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->jsonResponse());

        $logs = $this->entityManager()->getRepository(ReadLog::class)->findBy([
            'book' => $this->id(BookFixtures::FOUNDATION),
            'reader' => $this->id(PersonFixtures::ALICE),
        ]);
        self::assertCount(1, $logs);
        self::assertSame((new \DateTime())->format('Y-m-d'), $logs[0]->getStartDate()->format('Y-m-d'));
        self::assertNull($logs[0]->getFinishDate());
    }

    public function testStartReadingUnknownBookReturns404(): void
    {
        $this->chooseReader($this->id(PersonFixtures::ALICE));

        $this->client->request('GET', '/books/start/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testFinishReadingSetsTodayAsFinishDate(): void
    {
        $this->chooseReader($this->id(PersonFixtures::ALICE));

        $this->client->request('GET', '/books/finish/' . $this->id(BookFixtures::LOTR));

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->jsonResponse());

        $log = $this->entityManager()->getRepository(ReadLog::class)->findOneBy(['book' => $this->id(BookFixtures::LOTR)]);
        self::assertSame((new \DateTime())->format('Y-m-d'), $log->getFinishDate()?->format('Y-m-d'));
    }

    public function testFinishReadingOnlyFinishesTheCurrentReadersOpenLog(): void
    {
        // Foundation: bob is reading it. Alice starts it too, then finishes it.
        $this->chooseReader($this->id(PersonFixtures::ALICE));
        $this->client->request('GET', '/books/start/' . $this->id(BookFixtures::FOUNDATION));

        $this->client->request('GET', '/books/finish/' . $this->id(BookFixtures::FOUNDATION));
        self::assertResponseIsSuccessful();

        $logs = $this->entityManager()->getRepository(ReadLog::class);
        $alicesLog = $logs->findOneBy(['book' => $this->id(BookFixtures::FOUNDATION), 'reader' => $this->id(PersonFixtures::ALICE)]);
        $bobsLog = $logs->findOneBy(['book' => $this->id(BookFixtures::FOUNDATION), 'reader' => $this->id(PersonFixtures::BOB)]);

        self::assertNotNull($alicesLog->getFinishDate(), "Alice's log is finished");
        self::assertNull($bobsLog->getFinishDate(), "Bob is still reading");
    }

    public function testFinishReadingDoesNotChangeAlreadyFinishedLogs(): void
    {
        // The Hobbit: alice finished it twice (2024-07-12 and 2025-01-15) and is not reading it now.
        $this->chooseReader($this->id(PersonFixtures::ALICE));

        $this->client->request('GET', '/books/finish/' . $this->id(BookFixtures::HOBBIT));

        $finishDates = array_map(
            fn (ReadLog $log) => $log->getFinishDate()->format('Y-m-d'),
            $this->entityManager()->getRepository(ReadLog::class)->findBy(['book' => $this->id(BookFixtures::HOBBIT)], ['startDate' => 'ASC'])
        );
        self::assertSame(['2024-07-12', '2025-01-15'], $finishDates);
    }

    public function testFinishReadingABookWithoutLogsReturnsAnError(): void
    {
        $this->chooseReader($this->id(PersonFixtures::ALICE));
        $logsBefore = $this->entityManager()->getRepository(ReadLog::class)->count();

        $this->client->request('GET', '/books/finish/' . $this->id(BookFixtures::CLEAN_CODE));

        self::assertResponseStatusCodeSame(400);
        self::assertSame($logsBefore, $this->entityManager()->getRepository(ReadLog::class)->count());
    }

    // ── helpers ───────────────────────────────────────────────

    /**
     * Replaces the HTTP client used by OpenLibraryApi, so tests never call the real API.
     * $requestedUrl is set to a closure returning the URL of the last request.
     */
    private function mockOpenLibrary(MockResponse $response, ?\Closure &$requestedUrl = null): void
    {
        $url = null;
        $httpClient = new MockHttpClient(function (string $method, string $requested) use ($response, &$url) {
            $url = $requested;

            return $response;
        });
        $requestedUrl = static function () use (&$url): ?string {
            return $url;
        };

        static::getContainer()->set('http_client', $httpClient);
    }
}
