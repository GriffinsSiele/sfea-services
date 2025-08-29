<?php

declare(strict_types=1);

namespace App\Model;

use App\Form\Type\FormatType;
use App\Form\Type\RussianRegionType;
use App\Validator\Constraint\INN;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class CheckOrg
{
    #[NotBlank]
    #[INN]
    private ?string $inn = null;

    #[Choice(choices: RussianRegionType::DEFAULT_CHOICES)]
    private ?string $regionId = null;

    #[NotNull]
    #[Count(min: 1)]
    private ?array $sources = null;

    private ?bool $recursive = null;

    private ?bool $async = null;

    #[NotBlank]
    #[Choice(choices: FormatType::DEFAULT_CHOICES)]
    private ?string $format = null;

    public function setInn(?string $inn): self
    {
        $this->inn = $inn;

        return $this;
    }

    public function getInn(): ?string
    {
        return $this->inn;
    }

    public function setRegionId(?string $regionId): self
    {
        $this->regionId = $regionId;

        return $this;
    }

    public function getRegionId(): ?string
    {
        return $this->regionId;
    }

    public function setSources(?array $sources): self
    {
        $this->sources = $sources;

        return $this;
    }

    public function getSources(): ?array
    {
        return $this->sources;
    }

    public function setRecursive(?bool $recursive): self
    {
        $this->recursive = $recursive;

        return $this;
    }

    public function getRecursive(): ?bool
    {
        return $this->recursive;
    }

    public function setAsync(?bool $async): self
    {
        $this->async = $async;

        return $this;
    }

    public function getAsync(): ?bool
    {
        return $this->async;
    }

    public function setFormat(?string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }
}
