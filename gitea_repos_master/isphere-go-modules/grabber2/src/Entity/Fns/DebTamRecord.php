<?php

declare(strict_types=1);

namespace App\Entity\Fns;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'debtam_record', schema: 'fns')]
class DebTamRecord
{
    #[Column(type: Types::INTEGER)]
    #[Id]
    #[GeneratedValue(strategy: 'NONE')]
    private ?int $id = null;

    #[ManyToOne(targetEntity: DebTam::class)]
    #[JoinColumn('debtam_id', onDelete: 'CASCADE')]
    private ?DebTam $debTam = null;

    #[Column(type: Types::STRING)]
    private ?string $name = null;

    #[Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?float $tax = null;

    #[Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?float $penalty = null;

    #[Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?float $fine = null;

    #[Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?float $arrears = null;

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setDebTam(?DebTam $debTam): self
    {
        $this->debTam = $debTam;

        return $this;
    }

    public function getDebTam(): ?DebTam
    {
        return $this->debTam;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setTax(?float $tax): self
    {
        $this->tax = $tax;

        return $this;
    }

    public function getTax(): ?float
    {
        return $this->tax;
    }

    public function setPenalty(?float $penalty): self
    {
        $this->penalty = $penalty;

        return $this;
    }

    public function getPenalty(): ?float
    {
        return $this->penalty;
    }

    public function setFine(?float $fine): self
    {
        $this->fine = $fine;

        return $this;
    }

    public function getFine(): ?float
    {
        return $this->fine;
    }

    public function setArrears(?float $arrears): self
    {
        $this->arrears = $arrears;

        return $this;
    }

    public function getArrears(): ?float
    {
        return $this->arrears;
    }
}
