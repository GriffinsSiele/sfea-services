<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Annotation\SerializedName;

class OrgReq
{
    #[SerializedName('inn')]
    private ?string $inn = null;

    #[SerializedName('ogrn')]
    private ?string $ogrn = null;

    #[SerializedName('name')]
    private ?string $name = null;

    #[SerializedName('address')]
    private ?string $address = null;

    #[SerializedName('region_id')]
    private ?int $regionId = null;

    #[SerializedName('bik')]
    private ?string $bik = null;

    public function setInn(?string $inn): self
    {
        $this->inn = $inn;

        return $this;
    }

    public function getInn(): ?string
    {
        return $this->inn;
    }

    public function setOgrn(?string $ogrn): self
    {
        $this->ogrn = $ogrn;

        return $this;
    }

    public function getOgrn(): ?string
    {
        return $this->ogrn;
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

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setRegionId(?int $regionId): self
    {
        $this->regionId = $regionId;

        return $this;
    }

    public function getRegionId(): ?int
    {
        return $this->regionId;
    }

    public function setBik(?string $bik): self
    {
        $this->bik = $bik;

        return $this;
    }

    public function getBik(): ?string
    {
        return $this->bik;
    }
}
