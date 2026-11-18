<?php

namespace App\Tests\Functional\Controller;

use App\DataFixtures\BookFixtures;
use App\DataFixtures\PersonFixtures;
use App\Tests\Functional\ApplicationTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\DomCrawler\Crawler;

final class DashboardControllerTest extends ApplicationTestCase
{
    use ClockSensitiveTrait;

    protected function setUp(): void
    {
        parent::setUp();
        static::mockTime('2025-08-15 10:00:00');
    }

    public function testWithoutAReaderRedirectsToTheWelcomePage(): void
    {
        $this->client->request('GET', '/dashboard');

        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorExists('#reader');
    }

    public function testReaderIdInTheUrlChoosesTheReader(): void
    {
        $this->client->request('GET', '/dashboard?readerId=' . $this->id(PersonFixtures::BOB));

        self::assertResponseIsSuccessful();
        self::assertSame(['Foundation'], $this->readingNow());

        $this->client->request('GET', '/dashboard');
        self::assertResponseIsSuccessful();
        self::assertSame(['Foundation'], $this->readingNow());
    }

    public function testShowsTheMockedYearInTheHeadings(): void
    {
        $this->openDashboard(PersonFixtures::ALICE);

        self::assertSelectorTextContains('.card-finished h3', 'Books read this year (2025)');
        self::assertNotNull($this->card('Books brought to your shelf last year (2024)'));
    }

    public function testReadingNowShowsOnlyTheReadersOpenBooks(): void
    {
        $this->openDashboard(PersonFixtures::ALICE);
        self::assertSame(['The Lord of the Rings'], $this->readingNow());

        $this->openDashboard(PersonFixtures::BOB);
        self::assertSame(['Foundation'], $this->readingNow());

        $this->openDashboard(PersonFixtures::CAROL);
        self::assertSame([], $this->readingNow());
    }

    public function testBooksReadThisYear(): void
    {
        $this->openDashboard(PersonFixtures::ALICE);
        self::assertSame(['The Hobbit', 'Project Hail Mary', 'Snow Crash'], $this->texts('.card-finished li'));

        $this->openDashboard(PersonFixtures::BOB);
        self::assertSame(['Neuromancer'], $this->texts('.card-finished li'));

        $this->openDashboard(PersonFixtures::CAROL);
        self::assertSame([], $this->texts('.card-finished li'));
    }

    public function testStatisticsForThisYear(): void
    {
        $this->openDashboard(PersonFixtures::ALICE);
        self::assertSame(['3 books', '780 pages', '10.33 days'], $this->stats('Statistics for this year'));

        $this->openDashboard(PersonFixtures::BOB);
        self::assertSame(['1 books', '271 pages', '19 days'], $this->stats('Statistics for this year'));
    }

    public function testStatisticsForThisYearWithoutReadingActivity(): void
    {
        $this->openDashboard(PersonFixtures::CAROL);

        self::assertSame(['0 books', '0 pages', '0 days'], $this->stats('Statistics for this year'));
    }

    public function testTotalStatisticsCoverOnlyTheReadersLibrary(): void
    {
        $this->openDashboard(PersonFixtures::ALICE);
        self::assertSame(['8', '4', '50%', '4'], $this->stats('Total Statistics'));

        $this->openDashboard(PersonFixtures::BOB);
        self::assertSame(['2', '1', '50%', '1'], $this->stats('Total Statistics'));
    }

    public function testTotalStatisticsWithAnEmptyLibrary(): void
    {
        $this->openDashboard(PersonFixtures::CAROL);

        self::assertSame(['0', '0', '0%', '0'], $this->stats('Total Statistics'));
    }

    public function testBooksBoughtThisYearShowTheReadersBooksWithTheirStatus(): void
    {
        $this->openDashboard(PersonFixtures::ALICE);
        $card = $this->card('New books on the shelf this year (2025)');
        self::assertStringContainsString('The library has received 2 books', $card->text());
        self::assertSame(['Snow Crash' => '(done)', 'Kindred' => ''], $this->statuses($card));

        $this->openDashboard(PersonFixtures::BOB);
        $card = $this->card('New books on the shelf this year (2025)');
        self::assertStringContainsString('The library has received 1 books', $card->text());
        self::assertSame(['Foundation' => '(reading now)'], $this->statuses($card));

        $this->openDashboard(PersonFixtures::CAROL);
        $card = $this->card('New books on the shelf this year (2025)');
        self::assertStringContainsString('The library has received 0 books', $card->text());
        self::assertSame([], $this->statuses($card));
    }

    public function testStatusOfARereadBookComesFromTheLatestReading(): void
    {
        $this->chooseReader($this->id(PersonFixtures::ALICE));
        $this->client->request('GET', '/books/start/' . $this->id(BookFixtures::SNOW_CRASH));
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/dashboard');

        self::assertSame(
            ['Snow Crash' => '(reading now)', 'Kindred' => ''],
            $this->statuses($this->card('New books on the shelf this year (2025)'))
        );
    }

    public function testBooksBoughtLastYearShowTheReadersBooksWithTheirStatusAndProgress(): void
    {
        $this->openDashboard(PersonFixtures::ALICE);
        $card = $this->card('Books brought to your shelf last year (2024)');
        self::assertStringContainsString('The library has received 3 books', $card->text());
        self::assertSame(
            ['Project Hail Mary' => '(done)', 'Solaris' => '', 'The Lord of the Rings' => '(reading now)'],
            $this->sortedStatuses($card)
        );
        self::assertStringContainsString('So you have read 1 books from this list (33.3%)', $this->normalize($card->text()));

        $this->openDashboard(PersonFixtures::BOB);
        $card = $this->card('Books brought to your shelf last year (2024)');
        self::assertStringContainsString('The library has received 1 books', $card->text());
        self::assertSame(['Neuromancer' => '(done)'], $this->sortedStatuses($card));
        self::assertStringContainsString('So you have read 1 books from this list (100%)', $this->normalize($card->text()));
    }

    public function testBooksBoughtLastYearWithAnEmptyLibrary(): void
    {
        $this->openDashboard(PersonFixtures::CAROL);
        $card = $this->card('Books brought to your shelf last year (2024)');

        self::assertStringContainsString('The library has received 0 books', $card->text());
        self::assertSame([], $this->statuses($card));
        self::assertStringContainsString('So you have read 0 books from this list 0%', $this->normalize($card->text()));
    }

    public function testLibraryListsOnlyTheReadersBooks(): void
    {
        $this->chooseReader($this->id(PersonFixtures::BOB));

        $this->client->request('GET', '/books');

        self::assertResponseIsSuccessful();
        self::assertSame(['Neuromancer', 'Foundation'], $this->texts('tr[id^="tr-book-"] .book-title'));
        self::assertSelectorTextContains('.search-container', '2 books found');
    }

    public function testLibrarySearchByIdDoesNotFindAnotherReadersBook(): void
    {
        $this->chooseReader($this->id(PersonFixtures::BOB));

        $this->client->request('GET', '/books?id=' . $this->id(BookFixtures::DUNE));

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->texts('tr[id^="tr-book-"] .book-title'));
        self::assertSelectorTextContains('.search-container', '0 books found');
    }

    private function openDashboard(string $reader): void
    {
        $this->chooseReader($this->id($reader));
        $this->client->request('GET', '/dashboard');
        self::assertResponseIsSuccessful();
    }

    private function readingNow(): array
    {
        return array_map(
            fn (string $text) => trim(str_replace('(open in Library)', '', $text)),
            $this->texts('.card-reading li')
        );
    }

    private function card(string $heading): Crawler
    {
        $cards = $this->client->getCrawler()->filter('.dashboard-card')->reduce(
            fn (Crawler $card) => str_starts_with($this->normalize($card->filter('h3')->text()), $heading)
        );

        self::assertCount(1, $cards, sprintf('Expected one dashboard card with heading "%s"', $heading));

        return $cards->first();
    }

    private function stats(string $heading): array
    {
        return $this->card($heading)->filter('.highlight-stat')->each(fn (Crawler $node) => $this->normalize($node->text()));
    }

    private function texts(string $selector): array
    {
        return $this->client->getCrawler()->filter($selector)->each(fn (Crawler $node) => $this->normalize($node->text()));
    }

    private function statuses(Crawler $card): array
    {
        $statuses = [];
        $card->filter('ul ul > li')->each(function (Crawler $item) use (&$statuses) {
            $status = $item->filter('.highlight-current-status');
            $statusText = $status->count() ? $this->normalize($status->text()) : '';
            $title = $this->normalize(str_replace($statusText, '', $item->text()));
            $statuses[$title] = $statusText;
        });

        return $statuses;
    }

    private function sortedStatuses(Crawler $card): array
    {
        $statuses = $this->statuses($card);
        ksort($statuses);

        return $statuses;
    }

    private function sortedTitles(Crawler $card): array
    {
        return array_keys($this->sortedStatuses($card));
    }

    private function normalize(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
