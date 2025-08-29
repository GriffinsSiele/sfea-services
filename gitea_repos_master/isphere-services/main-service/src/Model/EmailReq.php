<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Annotation\SerializedName;

class EmailReq
{
    #[SerializedName('email')]
    private ?string $email = null;

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
}
