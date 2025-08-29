<?php

declare(strict_types=1);

namespace App\Model;

class IPReq
{
    private ?string $ip = null;

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }
}
