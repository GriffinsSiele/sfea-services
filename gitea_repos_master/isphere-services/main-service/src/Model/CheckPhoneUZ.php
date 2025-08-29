<?php

declare(strict_types=1);

namespace App\Model;

use App\Form\Type\FormatType;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class CheckPhoneUZ
{
    #[NotBlank]
    private ?string $mobilePhone = null;

    #[NotNull]
    #[Count(min: 1)]
    private ?array $sources = null;

    private ?bool $recursive = null;

    private ?bool $async = null;

    #[NotBlank]
    #[Choice(choices: FormatType::DEFAULT_CHOICES)]
    private ?string $format = null;

    public function setMobilePhone(?string $mobilePhone): self
    {
        $this->mobilePhone = $mobilePhone;

        return $this;
    }

    public function getMobilePhone(): ?string
    {
        return $this->mobilePhone;
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
