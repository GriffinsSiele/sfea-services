<?php

declare(strict_types=1);

namespace App\Model\Fns;

use Symfony\Component\Serializer\Annotation\SerializedName;

class Organization
{
    #[SerializedName('@НаимОрг')]
    private ?string $name = null;

    #[SerializedName('@ИННЮЛ')]
    private ?string $inn = null;

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setInn(null|int|string $inn): self
    {
        if (\is_int($inn)) {
            $inn = \str_pad((string) $inn, 10, '0', \STR_PAD_LEFT);
        }

        $this->inn = $inn;

        return $this;
    }

    public function getInn(): ?string
    {
        return $this->inn;
    }
}
