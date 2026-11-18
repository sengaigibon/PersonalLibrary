<?php

namespace App\Tests\Functional;

use App\Tests\LoadsFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base class for functional (application) tests: real HTTP requests through routing,
 * controllers, forms, Doctrine and Twig, against the test database.
 *
 * Functional tests change data (create, edit, delete...), so the fixtures are
 * purged and reloaded before every test.
 *
 * The schema must exist: run `make test-db` once (and again after adding a migration).
 */
abstract class ApplicationTestCase extends WebTestCase
{
    use LoadsFixtures;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        self::loadFixtures($this->entityManager(), self::$kernel->getEnvironment());
    }

    /**
     * Chooses the current reader the same way the welcome page does.
     */
    protected function chooseReader(int $readerId): void
    {
        $this->client->jsonRequest('POST', '/setReader', ['readerId' => $readerId]);
        self::assertResponseIsSuccessful();
    }

    /**
     * The entity manager of the current kernel, cleared so that reads hit the database
     * (the client reboots the kernel between requests).
     */
    protected function entityManager(): EntityManagerInterface
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        return $entityManager;
    }

    /**
     * @return array<mixed>
     */
    protected function jsonResponse(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
