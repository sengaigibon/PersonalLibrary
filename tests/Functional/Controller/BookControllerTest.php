<?php

namespace App\Tests\Functional\Controller;

use App\DataFixtures\BookFixtures;
use App\DataFixtures\PersonFixtures;
use App\DataFixtures\ReadLogFixtures;
use App\Entity\Book;
use App\Entity\ReadLog;
use App\Tests\Functional\ApplicationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class BookControllerTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->chooseReader($this->id(PersonFixtures::ALICE));
    }

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
        self::assertSelectorNotExists('form [name="book[owner]"]');
    }

    public function testNewCreatesTheBookInTheReadersLibraryAndRedirectsKeepingPagination(): void
    {
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
        self::assertSame($this->id(PersonFixtures::ALICE), $book->getOwner()->getId());
        self::assertSame('Dan Simmons', $book->getAuthor());
        self::assertSame('9780553283686', $book->getIsbn());
        self::assertSame(482, $book->getPages());
        self::assertSame('2025-08-01', $book->getPurchaseDate()?->format('Y-m-d'));
        self::assertFalse($book->isReference());

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Book created successfully!');
    }

    public function testNewRejectsADuplicateTitleInTheSameLibrary(): void
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

    public function testNewAllowsATitleThatExistsInAnotherLibrary(): void
    {
        $this->chooseReader($this->id(PersonFixtures::BOB));
        $this->client->request('GET', '/books/new');

        $this->client->submitForm('Save', [
            'book[title]' => 'Dune',
            'book[author]' => 'Frank Herbert',
        ]);

        self::assertResponseRedirects('/books?page=1&limit=20', 303);
        self::assertSame(1, $this->entityManager()->getRepository(Book::class)->count(['title' => 'Dune', 'owner' => $this->id(PersonFixtures::BOB)]));
        self::assertSame(1, $this->entityManager()->getRepository(Book::class)->count(['title' => 'Dune', 'owner' => $this->id(PersonFixtures::ALICE)]));
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
        self::assertSelectorTextContains('form', 'This value should not be blank.');
        self::assertSame($booksBefore, $this->entityManager()->getRepository(Book::class)->count());
    }

    public function testNewRejectsInvalidValues(): void
    {
        $this->client->request('GET', '/books/new');

        $this->client->submitForm('Save', [
            'book[title]' => 'Hyperion',
            'book[author]' => 'Dan Simmons',
            'book[isbn]' => '97805532836860000',
            'book[pages]' => '-5',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'This value is too long. It should have 13 characters or less.');
        self::assertSelectorTextContains('form', 'This value should be positive.');
        self::assertNull($this->entityManager()->getRepository(Book::class)->findOneBy(['title' => 'Hyperion']));
    }

    public function testNewBackLinkKeepsPagination(): void
    {
        $this->client->request('GET', '/books/new?page=4&limit=50');

        self::assertSelectorExists('a.btn-back[href="/books?page=4&limit=50"]');
    }

    public function testEditUpdatesTheBookAndRedirectsToTheLibraryKeepingPagination(): void
    {
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
        self::assertSame($this->id(PersonFixtures::ALICE), $book->getOwner()->getId(), 'The owner does not change');

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Book saved successfully!');
    }

    public function testEditWithAnEmptyTitleShowsTheFormAgainAndKeepsTheBook(): void
    {
        $this->client->request('GET', '/books/' . $this->id(BookFixtures::DUNE) . '/edit');

        $this->client->submitForm('Update Book', [
            'book[title]' => '',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'This value should not be blank.');
        self::assertSame('Dune', $this->entityManager()->find(Book::class, $this->id(BookFixtures::DUNE))->getTitle());
    }

    public function testEditUnknownBookReturns404(): void
    {
        $this->client->request('GET', '/books/999999/edit');

        self::assertResponseStatusCodeSame(404);
    }

    public function testDeleteRemovesTheBook(): void
    {
        $bookId = $this->id(BookFixtures::CLEAN_CODE);
        $this->client->request('GET', '/books/' . $bookId . '/edit');

        $this->client->submit($this->client->getCrawler()->filter('form[action="/books/' . $bookId . '"]')->form());

        self::assertResponseRedirects('/books', 303);
        self::assertNull($this->entityManager()->find(Book::class, $bookId));

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Book deleted successfully!');
    }

    public function testDeleteIsBlockedWhenTheBookHasReadingLogs(): void
    {
        $bookId = $this->id(BookFixtures::HOBBIT);
        $this->client->request('GET', '/books/' . $bookId . '/edit');

        $this->client->submit($this->client->getCrawler()->filter('form[action="/books/' . $bookId . '"]')->form());

        self::assertResponseRedirects('/books/' . $bookId, 303);
        self::assertNotNull($this->entityManager()->find(Book::class, $bookId));
        self::assertSame(2, $this->entityManager()->getRepository(ReadLog::class)->count(['book' => $bookId]));

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', "This book can't be deleted because it has 2 reading log entries.");
    }

    public function testDeleteWithAnInvalidCsrfTokenKeepsTheBook(): void
    {
        $bookId = $this->id(BookFixtures::CLEAN_CODE);

        $this->client->request('POST', '/books/' . $bookId, ['_token' => 'invalid']);

        self::assertResponseRedirects('/books', 303);
        self::assertNotNull($this->entityManager()->find(Book::class, $bookId));

        $this->client->followRedirect();
        self::assertSelectorNotExists('.alert-success');
        self::assertSelectorTextContains('.alert-danger', 'The book could not be deleted');
    }

    #[DataProvider('pagesOfAnotherReadersBookProvider')]
    public function testPagesOfAnotherReadersBookReturn404(string $method, string $path, array $parameters = []): void
    {
        $this->client->request($method, sprintf($path, $this->id(BookFixtures::NEUROMANCER)), $parameters);

        self::assertResponseStatusCodeSame(404);
    }

    public static function pagesOfAnotherReadersBookProvider(): iterable
    {
        yield 'show' => ['GET', '/books/%d'];
        yield 'edit form' => ['GET', '/books/%d/edit'];
        yield 'edit submit' => ['POST', '/books/%d/edit', ['book' => ['title' => 'Hacked', 'author' => 'Hacked']]];
        yield 'delete' => ['POST', '/books/%d', ['_token' => 'any']];
        yield 'start reading' => ['GET', '/books/start/%d'];
        yield 'finish reading' => ['GET', '/books/finish/%d'];
    }

    public function testAnotherReadersBookIsNotChanged(): void
    {
        $bookId = $this->id(BookFixtures::NEUROMANCER);

        $this->client->request('POST', '/books/' . $bookId . '/edit', ['book' => ['title' => 'Hacked', 'author' => 'Hacked']]);
        $this->client->request('POST', '/books/' . $bookId, ['_token' => 'any']);
        $this->client->request('GET', '/books/start/' . $bookId);

        $book = $this->entityManager()->find(Book::class, $bookId);
        self::assertNotNull($book);
        self::assertSame('Neuromancer', $book->getTitle());
        self::assertSame(0, $this->entityManager()->getRepository(ReadLog::class)->count(['book' => $bookId, 'reader' => $this->id(PersonFixtures::ALICE)]));
    }

    #[DataProvider('pagesWithoutAReaderProvider')]
    public function testPagesWithoutAReaderRedirectToTheWelcomePage(string $method, string $path): void
    {
        $this->leaveLibrary();

        $this->client->request($method, sprintf($path, $this->id(BookFixtures::DUNE)));

        self::assertResponseRedirects('/');
    }

    public static function pagesWithoutAReaderProvider(): iterable
    {
        yield 'new' => ['GET', '/books/new'];
        yield 'show' => ['GET', '/books/%d'];
        yield 'edit' => ['GET', '/books/%d/edit'];
        yield 'delete' => ['POST', '/books/%d'];
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
        $this->leaveLibrary();

        $this->client->request('GET', '/books/start/' . $this->id(BookFixtures::KINDRED));

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['error' => 'No reader has been set, go to the welcome page'], $this->jsonResponse());
        self::assertSame(0, $this->entityManager()->getRepository(ReadLog::class)->count(['book' => $this->id(BookFixtures::KINDRED)]));
    }

    public function testStartReadingCreatesALogForTheCurrentReaderStartingToday(): void
    {
        $this->client->request('GET', '/books/start/' . $this->id(BookFixtures::KINDRED));

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->jsonResponse());

        $logs = $this->entityManager()->getRepository(ReadLog::class)->findBy(['book' => $this->id(BookFixtures::KINDRED)]);
        self::assertCount(1, $logs);
        self::assertSame($this->id(PersonFixtures::ALICE), $logs[0]->getReader()->getId());
        self::assertSame((new \DateTime())->format('Y-m-d'), $logs[0]->getStartDate()->format('Y-m-d'));
        self::assertNull($logs[0]->getFinishDate());
    }

    public function testStartReadingUnknownBookReturns404(): void
    {
        $this->client->request('GET', '/books/start/999999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testFinishReadingSetsTodayAsFinishDate(): void
    {
        $this->client->request('GET', '/books/finish/' . $this->id(BookFixtures::LOTR));

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], $this->jsonResponse());

        $log = $this->entityManager()->find(ReadLog::class, $this->id(ReadLogFixtures::ALICE_LOTR));
        self::assertSame((new \DateTime())->format('Y-m-d'), $log->getFinishDate()?->format('Y-m-d'));
    }

    public function testFinishReadingOnlyFinishesTheOpenLogOfARereadBook(): void
    {
        $this->client->request('GET', '/books/start/' . $this->id(BookFixtures::HOBBIT));
        $this->client->request('GET', '/books/finish/' . $this->id(BookFixtures::HOBBIT));

        self::assertResponseIsSuccessful();

        $finishDates = array_map(
            fn (ReadLog $log) => $log->getFinishDate()?->format('Y-m-d'),
            $this->entityManager()->getRepository(ReadLog::class)->findBy(['book' => $this->id(BookFixtures::HOBBIT)], ['startDate' => 'ASC'])
        );
        self::assertSame(['2024-07-12', '2025-01-15', (new \DateTime())->format('Y-m-d')], $finishDates);
    }

    public function testFinishReadingDoesNotChangeAlreadyFinishedLogs(): void
    {
        $this->client->request('GET', '/books/finish/' . $this->id(BookFixtures::HOBBIT));

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['error' => 'You are not reading this book, start reading it first'], $this->jsonResponse());

        $finishDates = array_map(
            fn (ReadLog $log) => $log->getFinishDate()->format('Y-m-d'),
            $this->entityManager()->getRepository(ReadLog::class)->findBy(['book' => $this->id(BookFixtures::HOBBIT)], ['startDate' => 'ASC'])
        );
        self::assertSame(['2024-07-12', '2025-01-15'], $finishDates);
    }

    public function testFinishReadingWithoutAReaderReturnsAnError(): void
    {
        $this->leaveLibrary();

        $this->client->request('GET', '/books/finish/' . $this->id(BookFixtures::LOTR));

        self::assertResponseStatusCodeSame(400);
        self::assertSame(['error' => 'No reader has been set, go to the welcome page'], $this->jsonResponse());
        self::assertNull($this->entityManager()->find(ReadLog::class, $this->id(ReadLogFixtures::ALICE_LOTR))->getFinishDate());
    }

    public function testFinishingAnotherReadersBookDoesNotChangeTheirLog(): void
    {
        $this->client->request('GET', '/books/finish/' . $this->id(BookFixtures::FOUNDATION));

        self::assertResponseStatusCodeSame(404);
        self::assertNull($this->entityManager()->find(ReadLog::class, $this->id(ReadLogFixtures::BOB_FOUNDATION))->getFinishDate());
    }

    public function testFinishReadingABookWithoutLogsReturnsAnError(): void
    {
        $logsBefore = $this->entityManager()->getRepository(ReadLog::class)->count();

        $this->client->request('GET', '/books/finish/' . $this->id(BookFixtures::CLEAN_CODE));

        self::assertResponseStatusCodeSame(400);
        self::assertSame($logsBefore, $this->entityManager()->getRepository(ReadLog::class)->count());
    }

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

        // http_client is already initialized by the request in setUp(), so it can only be replaced in a fresh kernel
        $this->client->getKernel()->shutdown();
        $this->client->getKernel()->boot();
        $this->client->disableReboot();
        static::getContainer()->set('http_client', $httpClient);
    }
}
