<?php

declare(strict_types=1);

namespace App\Entity\Fns;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'debtam', schema: 'fns')]
#[Index(fields: ['inn'], name: 'debtam_inn_idx')]
class DebTam
{
    #[Column(type: Types::STRING)]
    #[Id]
    #[GeneratedValue(strategy: 'NONE')]
    private ?string $id = null;

    #[Column(type: Types::STRING, length: 10)]
    private ?string $inn = null;

    #[Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[OneToMany(mappedBy: 'debTam', targetEntity: DebTamRecord::class)]
    private ?Collection $records = null;

    public function __construct()
    {
        $this->records = new ArrayCollection();
    }

    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setInn(?string $inn): self
    {
        $this->inn = $inn;

        return $this;
    }

    public function getInn(): ?string
    {
        return $this->inn;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function addRecord(DebTamRecord $record): self
    {
        $this->records->add($record->setDebTam($this));

        return $this;
    }

    public function removeRecord(DebTamRecord $record): self
    {
        $this->records->removeElement($record->setDebTam(null));

        return $this;
    }

    public function getRecords(): ?Collection
    {
        return $this->records;
    }
}
