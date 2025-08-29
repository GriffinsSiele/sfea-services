<?php

declare(strict_types=1);

namespace App\Model\Fns;

use Symfony\Component\Serializer\Annotation\SerializedName;

class Underpayment
{
    #[SerializedName('@НаимНалог')]
    private ?string $name = null;

    #[SerializedName('@СумНедНалог')]
    private ?float $tax = null;

    #[SerializedName('@СумПени')]
    private ?float $penalty = null;

    #[SerializedName('@СумШтраф')]
    private ?float $fine = null;

    #[SerializedName('@ОбщСумНедоим')]
    private ?float $arrears = null;

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
