<?php

namespace App\Tests\Integration;

use App\Tests\LoadsFixtures;
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
    use LoadsFixtures;

    protected EntityManagerInterface $entityManager;

    public static function setUpBeforeClass(): void
    {
        $kernel = self::bootKernel();
        self::loadFixtures(self::getContainer()->get(EntityManagerInterface::class), $kernel->getEnvironment());
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
