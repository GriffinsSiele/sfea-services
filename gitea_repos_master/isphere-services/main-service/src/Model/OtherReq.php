<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Annotation\SerializedName;

class OtherReq
{
    #[SerializedName('osago')]
    private ?string $osago = null;

    public function setOsago(?string $osago): self
    {
        $this->osago = $osago;

        return $this;
    }

    public function getOsago(): ?string
    {
        return $this->osago;
    }
}
