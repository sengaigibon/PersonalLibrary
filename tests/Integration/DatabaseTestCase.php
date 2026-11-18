<?php

namespace App\Tests\Integration;

use App\DataFixtures\BookFixtures;
use App\DataFixtures\PersonFixtures;
use App\DataFixtures\ReadLogFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base class for tests that need the test database ("<db>_test", see config/packages/doctrine.yaml).
 *
 * The fixtures are purged and reloaded once per test class, so every test class starts from the
 * same known data. Fixture IDs change on every load, so tests look them up by reference name:
 *
 *     $this->id(BookFixtures::DUNE)
 *
 * The schema must exist: run `make test-db` once (and again after adding a migration).
 */
abstract class DatabaseTestCase extends KernelTestCase
{
    /** @var array<string, int> fixture reference name => entity id */
    private static array $ids = [];

    protected EntityManagerInterface $entityManager;

    public static function setUpBeforeClass(): void
    {
        $kernel = self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $database = $entityManager->getConnection()->getDatabase();
        if ($kernel->getEnvironment() !== 'test' || !str_contains((string) $database, '_test')) {
            throw new \LogicException(sprintf(
                'Refusing to purge database "%s" in the "%s" environment: integration tests must run against the test database.',
                $database,
                $kernel->getEnvironment()
            ));
        }

        $loader = new Loader();
        $loader->addFixture(new PersonFixtures());
        $loader->addFixture(new BookFixtures());
        $loader->addFixture(new ReadLogFixtures());

        $executor = new ORMExecutor($entityManager, new ORMPurger($entityManager));
        $executor->execute($loader->getFixtures());

        self::$ids = [];
        foreach ($executor->getReferenceRepository()->getReferencesByClass() as $references) {
            foreach ($references as $name => $entity) {
                self::$ids[$name] = $entity->getId();
            }
        }

        self::ensureKernelShutdown();
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->entityManager);
    }

    /**
     * The database id of a fixture, e.g. $this->id(PersonFixtures::ALICE).
     */
    protected function id(string $reference): int
    {
        if (!isset(self::$ids[$reference])) {
            throw new \InvalidArgumentException(sprintf('Unknown fixture reference "%s".', $reference));
        }

        return self::$ids[$reference];
    }

    /**
     * Runs $callback inside a transaction that is always rolled back,
     * so tests can add data without affecting the other tests.
     */
    protected function inRolledBackTransaction(callable $callback): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $callback();
        } finally {
            $connection->rollBack();
            $this->entityManager->clear();
        }
    }
}
