<?php

declare(strict_types=1);

namespace App\Model;

class URLReq
{
    private ?string $url = null;

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
