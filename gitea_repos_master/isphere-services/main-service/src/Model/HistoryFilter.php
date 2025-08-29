<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Client;
use App\Entity\SystemUser;
use Symfony\Component\Validator\Constraints\Valid;

class HistoryFilter
{
    private ?Client $client = null;

    private ?SystemUser $user = null;

    private ?bool $nested = null;

    #[Valid]
    private ?Period $period = null;

    private ?string $source = null;

    private ?string $checkType = null;

    private ?int $statusCode = null;

    private ?int $page = null;

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setUser(?SystemUser $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?SystemUser
    {
        return $this->user;
    }

    public function setNested(?bool $nested): self
    {
        $this->nested = $nested;

        return $this;
    }

    public function getNested(): ?bool
    {
        return $this->nested;
    }

    public function setPeriod(?Period $period): self
    {
        $this->period = $period;

        return $this;
    }

    public function getPeriod(): ?Period
    {
        return $this->period;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setCheckType(?string $checkType): self
    {
        $this->checkType = $checkType;

        return $this;
    }

    public function getCheckType(): ?string
    {
        return $this->checkType;
    }

    public function setStatusCode(?int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function setPage(?int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }
}
