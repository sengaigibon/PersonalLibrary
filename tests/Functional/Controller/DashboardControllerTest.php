<?php

namespace App\Tests\Functional\Controller;

use App\DataFixtures\PersonFixtures;
use App\Tests\Functional\ApplicationTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The dashboard depends on the current year, so every test fixes the date to 2025-08-15.
 * The expected values come from the fixtures (see src/DataFixtures/*.php):
 *
 * |                       | alice                                | bob                  | carol |
 * |-----------------------|--------------------------------------|----------------------|-------|
 * | Read in 2025          | The Hobbit, Project Hail Mary,       | Neuromancer          | -     |
 * |                       | Snow Crash                           |                      |       |
 * | Pages / avg days      | 780 / 10.33 (10 + 0 + 21 days)       | 271 / 19             | 0 / 0 |
 * | Reading now           | The Lord of the Rings                | Foundation           | -     |
 * | Finished (all time)   | 4 of 10 (40%)                        | 1 of 10 (10%)        | 0     |
 *
 * Books bought in 2025: Foundation, Snow Crash, Kindred.
 * Books bought in 2024: The Lord of the Rings, Neuromancer, Project Hail Mary, Solaris.
 * The library is shared: every reader sees every book bought in a year, with their own status.
 */
final class DashboardControllerTest extends ApplicationTestCase
{
    use ClockSensitiveTrait;

    protected function setUp(): void
    {
        parent::setUp();
        static::mockTime('2025-08-15 10:00:00');
    }

    // ── access ────────────────────────────────────────────────

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

        // The reader is remembered for the next requests
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

    // ── reading now ───────────────────────────────────────────

    public function testReadingNowShowsOnlyTheReadersOpenBooks(): void
    {
        $this->openDashboard(PersonFixtures::ALICE);
        self::assertSame(['The Lord of the Rings'], $this->readingNow());

        $this->openDashboard(PersonFixtures::BOB);
        self::assertSame(['Foundation'], $this->readingNow());

        $this->openDashboard(PersonFixtures::CAROL);
        self::assertSame([], $this->readingNow());
    }

    // ── this year ─────────────────────────────────────────────

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

    // ── total statistics ──────────────────────────────────────

    public function testTotalStatistics(): void
    {
        $this->openDashboard(PersonFixtures::ALICE);
        self::assertSame(['10', '4', '40%', '6'], $this->stats('Total Statistics'));

        $this->openDashboard(PersonFixtures::BOB);
        self::assertSame(['10', '1', '10%', '9'], $this->stats('Total Statistics'));

        $this->openDashboard(PersonFixtures::CAROL);
        self::assertSame(['10', '0', '0%', '10'], $this->stats('Total Statistics'));
    }

    // ── books bought this year ────────────────────────────────

    public function testBooksBoughtThisYearShowsEveryBookBoughtThatYear(): void
    {
        foreach ([PersonFixtures::ALICE, PersonFixtures::BOB, PersonFixtures::CAROL] as $reader) {
            $this->openDashboard($reader);
            $card = $this->card('New books on the shelf this year (2025)');

            self::assertStringContainsString('The library has received 3 books', $card->text(), $reader);
            self::assertSame(['Foundation', 'Kindred', 'Snow Crash'], $this->sortedTitles($card), $reader);
        }
    }

    public function testBooksBoughtThisYearShowTheReadersOwnStatus(): void
    {
        $this->openDashboard(PersonFixtures::ALICE);
        self::assertSame(
            ['Foundation' => '', 'Snow Crash' => '(done)', 'Kindred' => ''],
            $this->statuses($this->card('New books on the shelf this year (2025)'))
        );

        $this->openDashboard(PersonFixtures::BOB);
        self::assertSame(
            ['Foundation' => '(reading now)', 'Snow Crash' => '', 'Kindred' => ''],
            $this->statuses($this->card('New books on the shelf this year (2025)'))
        );
    }

    // ── books bought last year ────────────────────────────────

    public function testBooksBoughtLastYearShowsEveryBookBoughtThatYear(): void
    {
        foreach ([PersonFixtures::ALICE, PersonFixtures::BOB, PersonFixtures::CAROL] as $reader) {
            $this->openDashboard($reader);
            $card = $this->card('Books brought to your shelf last year (2024)');

            self::assertStringContainsString('The library has received 4 books', $card->text(), $reader);
            self::assertSame(['Neuromancer', 'Project Hail Mary', 'Solaris', 'The Lord of the Rings'], $this->sortedTitles($card), $reader);
        }
    }

    public function testBooksBoughtLastYearShowTheReadersOwnStatusAndProgress(): void
    {
        $this->openDashboard(PersonFixtures::ALICE);
        $card = $this->card('Books brought to your shelf last year (2024)');
        self::assertSame(
            ['Neuromancer' => '', 'Project Hail Mary' => '(done)', 'Solaris' => '', 'The Lord of the Rings' => '(reading now)'],
            $this->sortedStatuses($card)
        );
        self::assertStringContainsString('So you have read 1 books from this list (25%)', $this->normalize($card->text()));

        $this->openDashboard(PersonFixtures::BOB);
        $card = $this->card('Books brought to your shelf last year (2024)');
        self::assertSame(
            ['Neuromancer' => '(done)', 'Project Hail Mary' => '', 'Solaris' => '', 'The Lord of the Rings' => ''],
            $this->sortedStatuses($card)
        );
        self::assertStringContainsString('So you have read 1 books from this list (25%)', $this->normalize($card->text()));

        $this->openDashboard(PersonFixtures::CAROL);
        $card = $this->card('Books brought to your shelf last year (2024)');
        self::assertStringContainsString('So you have read 0 books from this list (0%)', $this->normalize($card->text()));
    }

    // ── helpers ───────────────────────────────────────────────

    private function openDashboard(string $reader): void
    {
        $this->chooseReader($this->id($reader));
        $this->client->request('GET', '/dashboard');
        self::assertResponseIsSuccessful();
    }

    /**
     * @return list<string> titles in the "Reading now" card
     */
    private function readingNow(): array
    {
        return array_map(
            fn (string $text) => trim(str_replace('(open in Library)', '', $text)),
            $this->texts('.card-reading li')
        );
    }

    /**
     * The dashboard card whose heading starts with $heading.
     */
    private function card(string $heading): Crawler
    {
        $cards = $this->client->getCrawler()->filter('.dashboard-card')->reduce(
            fn (Crawler $card) => str_starts_with($this->normalize($card->filter('h3')->text()), $heading)
        );

        self::assertCount(1, $cards, sprintf('Expected one dashboard card with heading "%s"', $heading));

        return $cards->first();
    }

    /**
     * @return list<string> the highlighted values of a card, in order
     */
    private function stats(string $heading): array
    {
        return $this->card($heading)->filter('.highlight-stat')->each(fn (Crawler $node) => $this->normalize($node->text()));
    }

    /**
     * @return list<string>
     */
    private function texts(string $selector): array
    {
        return $this->client->getCrawler()->filter($selector)->each(fn (Crawler $node) => $this->normalize($node->text()));
    }

    /**
     * @return array<string, string> book title => status shown next to it ("", "(done)" or "(reading now)"), in page order
     */
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

    /**
     * @return array<string, string> like statuses(), sorted by title
     */
    private function sortedStatuses(Crawler $card): array
    {
        $statuses = $this->statuses($card);
        ksort($statuses);

        return $statuses;
    }

    /**
     * @return list<string> book titles listed in a card, sorted
     */
    private function sortedTitles(Crawler $card): array
    {
        return array_keys($this->sortedStatuses($card));
    }

    private function normalize(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
