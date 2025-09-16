<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $author = null;

    #[ORM\Column(length: 13, nullable: true)]
    private ?string $isbn = null;

    #[ORM\Column(nullable: true)]
    private ?int $pages = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $purchaseDate = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private bool $isReference = false;

    /**
     * @var Collection<int, ReadLog>
     */
    #[ORM\OneToMany(targetEntity: ReadLog::class, mappedBy: 'book')]
    private Collection $readLogs;

    public function __construct()
    {
        $this->readLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): static
    {
        $this->isbn = $isbn;

        return $this;
    }

    public function getPages(): ?int
    {
        return $this->pages;
    }

    public function setPages(?int $pages): static
    {
        $this->pages = $pages;

        return $this;
    }

    public function getPurchaseDate(): ?\DateTime
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(?\DateTime $purchaseDate): static
    {
        $this->purchaseDate = $purchaseDate;

        return $this;
    }

    /**
     * @return Collection<int, ReadLog>
     */
    public function getReadLogs(): Collection
    {
        return $this->readLogs;
    }

    public function addReadLog(ReadLog $readLog): static
    {
        if (!$this->readLogs->contains($readLog)) {
            $this->readLogs->add($readLog);
            $readLog->setBook($this);
        }

        return $this;
    }

    public function removeReadLog(ReadLog $readLog): static
    {
        if ($this->readLogs->removeElement($readLog)) {
            // set the owning side to null (unless already changed)
            if ($readLog->getBook() === $this) {
                $readLog->setBook(null);
            }
        }

        return $this;
    }

    public function isReference(): bool
    {
        return $this->isReference;
    }

    public function setIsReference(bool $isReference): void
    {
        $this->isReference = $isReference;
    }
}
