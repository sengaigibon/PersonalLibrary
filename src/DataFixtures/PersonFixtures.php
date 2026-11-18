<?php

namespace App\DataFixtures;

use App\Entity\Person;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PersonFixtures extends Fixture
{
    public const string ALICE = 'reader-alice';
    public const string BOB = 'reader-bob';
    public const string CAROL = 'reader-carol';

    public function load(ObjectManager $manager): void
    {
        $readers = [
            self::ALICE => ['alice', 'Alice Johnson'],
            self::BOB => ['bob', 'Bob Smith'],
            self::CAROL => ['carol', 'Carol Davis'],
        ];

        foreach ($readers as $reference => [$nickname, $fullName]) {
            $person = new Person();
            $person->setNickname($nickname);
            $person->setFullName($fullName);

            $manager->persist($person);
            $this->addReference($reference, $person);
        }

        $manager->flush();
    }
}
