<?php

declare(strict_types=1);

namespace App\Model\Fns;

use Symfony\Component\Serializer\Annotation\SerializedName;

class Sender
{
    #[SerializedName('ФИООтв')]
    private ?Person $person = null;

    public function setPerson(?Person $person): self
    {
        $this->person = $person;

        return $this;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }
}
