<?php

namespace App\Tests;

use App\DataFixtures\BookFixtures;
use App\DataFixtures\PersonFixtures;
use App\DataFixtures\ReadLogFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Purges the test database and loads the fixtures from src/DataFixtures.
 *
 * Fixture IDs change on every load, so tests look them up by reference name:
 *
 *     $this->id(BookFixtures::DUNE)
 */
trait LoadsFixtures
{
    /** @var array<string, int> fixture reference name => entity id */
    private static array $fixtureIds = [];

    private static function loadFixtures(EntityManagerInterface $entityManager, string $environment): void
    {
        $database = $entityManager->getConnection()->getDatabase();
        if ($environment !== 'test' || !str_contains((string) $database, '_test')) {
            throw new \LogicException(sprintf(
                'Refusing to purge database "%s" in the "%s" environment: tests must run against the test database.',
                $database,
                $environment
            ));
        }

        $loader = new Loader();
        $loader->addFixture(new PersonFixtures());
        $loader->addFixture(new BookFixtures());
        $loader->addFixture(new ReadLogFixtures());

        $executor = new ORMExecutor($entityManager, new ORMPurger($entityManager));
        $executor->execute($loader->getFixtures());

        self::$fixtureIds = [];
        foreach ($executor->getReferenceRepository()->getReferencesByClass() as $references) {
            foreach ($references as $name => $entity) {
                self::$fixtureIds[$name] = $entity->getId();
            }
        }

        $entityManager->clear();
    }

    /**
     * The database id of a fixture, e.g. $this->id(PersonFixtures::ALICE).
     */
    protected function id(string $reference): int
    {
        if (!isset(self::$fixtureIds[$reference])) {
            throw new \InvalidArgumentException(sprintf('Unknown fixture reference "%s".', $reference));
        }

        return self::$fixtureIds[$reference];
    }
}
