<?php

declare(strict_types=1);

namespace App\Model;

class CardReq
{
    private ?string $card = null;

    public function setCard(?string $card): self
    {
        $this->card = $card;

        return $this;
    }

    public function getCard(): ?string
    {
        return $this->card;
    }
}
