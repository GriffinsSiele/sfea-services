<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Annotation\SerializedName;

class SkypeReq
{
    #[SerializedName('skype')]
    private ?string $skype = null;

    public function setSkype(?string $skype): self
    {
        $this->skype = $skype;

        return $this;
    }

    public function getSkype(): ?string
    {
        return $this->skype;
    }
}
