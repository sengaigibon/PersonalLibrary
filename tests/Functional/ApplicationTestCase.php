<?php

namespace App\Tests\Functional;

use App\Tests\LoadsFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApplicationTestCase extends WebTestCase
{
    use LoadsFixtures;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        self::loadFixtures($this->entityManager(), self::$kernel->getEnvironment());
    }

    protected function chooseReader(int $readerId): void
    {
        $this->client->jsonRequest('POST', '/setReader', ['readerId' => $readerId]);
        self::assertResponseIsSuccessful();
    }

    protected function leaveLibrary(): void
    {
        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
    }

    protected function entityManager(): EntityManagerInterface
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        return $entityManager;
    }

    protected function jsonResponse(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
