<?php

declare(strict_types=1);

namespace App\Model\Fns;

use Symfony\Component\Serializer\Annotation\SerializedName;

class Person
{
    #[SerializedName('@Фамилия')]
    private ?string $surname = null;

    #[SerializedName('@Имя')]
    private ?string $name = null;

    public function setSurname(?string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
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
}
