<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\SystemUser;
use Symfony\Component\Validator\Constraints\Valid;

class BulkFilter
{
    use PaginationTrait;

    private ?SystemUser $user = null;

    private ?bool $nested = null;

    #[Valid]
    private ?Period $period = null;

    private ?string $source = null;

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
}
