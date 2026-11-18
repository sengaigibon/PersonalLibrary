<?php

namespace App\Services;

use App\Entity\Book;
use App\Entity\Person;
use App\Repository\PersonRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CurrentReader
{
    public function __construct(
        private RequestStack $requestStack,
        private PersonRepository $personRepository,
    ) {
    }

    public function getId(): ?int
    {
        $readerId = $this->requestStack->getSession()->get('current_reader_id');

        return $readerId ? (int) $readerId : null;
    }

    public function get(): ?Person
    {
        $readerId = $this->getId();

        return $readerId ? $this->personRepository->find($readerId) : null;
    }

    public function owns(Book $book): bool
    {
        return $book->isOwnedBy($this->getId());
    }
}
