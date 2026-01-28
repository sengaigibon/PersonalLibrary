<?php

namespace App\Entity;

use App\Repository\PersonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

#[ORM\Entity(repositoryClass: PersonRepository::class)]
class Person implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nickname = null;

    #[ORM\Column(length: 200)]
    private ?string $fullName = null;

    /**
     * @var Collection<int, ReadLog>
     */
    #[ORM\OneToMany(targetEntity: ReadLog::class, mappedBy: 'Reader')]
    private Collection $Readings;

    public function __construct()
    {
        $this->Readings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): static
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * @return Collection<int, ReadLog>
     */
    public function getReadings(): Collection
    {
        return $this->Readings;
    }

    public function addReading(ReadLog $reading): static
    {
        if (!$this->Readings->contains($reading)) {
            $this->Readings->add($reading);
            $reading->setReader($this);
        }

        return $this;
    }

    public function removeReading(ReadLog $reading): static
    {
        if ($this->Readings->removeElement($reading)) {
            // set the owning side to null (unless already changed)
            if ($reading->getReader() === $this) {
                $reading->setReader(null);
            }
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nickname' => $this->nickname,
            'fullName' => $this->fullName,
        ];
    }
}
