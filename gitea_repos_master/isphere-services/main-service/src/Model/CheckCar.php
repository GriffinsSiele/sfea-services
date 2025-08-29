<?php

declare(strict_types=1);

namespace App\Model;

use App\Form\Type\FormatType;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class CheckCar
{
    private ?string $vin = null;

    private ?string $bodyNumber = null;

    private ?string $chassis = null;

    private ?string $regNumber = null;

    private ?string $ctc = null;

    private ?string $pts = null;

    private ?string $osago = null;

    private ?\DateTimeInterface $reqDate = null;

    private ?string $driverNumber = null;

    private ?\DateTimeInterface $driverDate = null;

    #[NotNull]
    #[Count(min: 1)]
    private ?array $sources = null;

    private ?bool $recursive = null;

    private ?bool $async = null;

    #[NotBlank]
    #[Choice(choices: FormatType::DEFAULT_CHOICES)]
    private ?string $format = null;

    public function setVin(?string $vin): self
    {
        $this->vin = $vin;

        return $this;
    }

    public function getVin(): ?string
    {
        return $this->vin;
    }

    public function setBodyNumber(?string $bodyNumber): self
    {
        $this->bodyNumber = $bodyNumber;

        return $this;
    }

    public function getBodyNumber(): ?string
    {
        return $this->bodyNumber;
    }

    public function setChassis(?string $chassis): self
    {
        $this->chassis = $chassis;

        return $this;
    }

    public function getChassis(): ?string
    {
        return $this->chassis;
    }

    public function setRegNumber(?string $regNumber): self
    {
        $this->regNumber = $regNumber;

        return $this;
    }

    public function getRegNumber(): ?string
    {
        return $this->regNumber;
    }

    public function setCtc(?string $ctc): self
    {
        $this->ctc = $ctc;

        return $this;
    }

    public function getCtc(): ?string
    {
        return $this->ctc;
    }

    public function setPts(?string $pts): self
    {
        $this->pts = $pts;

        return $this;
    }

    public function getPts(): ?string
    {
        return $this->pts;
    }

    public function setOsago(?string $osago): self
    {
        $this->osago = $osago;

        return $this;
    }

    public function getOsago(): ?string
    {
        return $this->osago;
    }

    public function setReqDate(?\DateTimeInterface $reqDate): self
    {
        $this->reqDate = $reqDate;

        return $this;
    }

    public function getReqDate(): ?\DateTimeInterface
    {
        return $this->reqDate;
    }

    public function setDriverNumber(?string $driverNumber): self
    {
        $this->driverNumber = $driverNumber;

        return $this;
    }

    public function getDriverNumber(): ?string
    {
        return $this->driverNumber;
    }

    public function setDriverDate(?\DateTimeInterface $driverDate): self
    {
        $this->driverDate = $driverDate;

        return $this;
    }

    public function getDriverDate(): ?\DateTimeInterface
    {
        return $this->driverDate;
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
