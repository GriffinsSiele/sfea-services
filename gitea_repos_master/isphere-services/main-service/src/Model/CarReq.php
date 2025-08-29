<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Annotation\SerializedName;

class CarReq
{
    #[SerializedName('vin')]
    private ?string $vin = null;

    #[SerializedName('bodynum')]
    private ?string $bodyNumber = null;

    #[SerializedName('chassis')]
    private ?string $chassis = null;

    #[SerializedName('regnum')]
    private ?string $number = null;

    #[SerializedName('ctc')]
    private ?string $registrationCertificateNumber = null;

    #[SerializedName('pts')]
    private ?string $passportNumber = null;

    #[SerializedName('reqdate')]
    private ?\DateTimeInterface $requestAt = null;

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

    public function setNumber(?string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setRegistrationCertificateNumber(?string $registrationCertificateNumber): self
    {
        $this->registrationCertificateNumber = $registrationCertificateNumber;

        return $this;
    }

    public function getRegistrationCertificateNumber(): ?string
    {
        return $this->registrationCertificateNumber;
    }

    public function setPassportNumber(?string $passportNumber): self
    {
        $this->passportNumber = $passportNumber;

        return $this;
    }

    public function getPassportNumber(): ?string
    {
        return $this->passportNumber;
    }

    public function setRequestAt(?\DateTimeInterface $requestAt): self
    {
        $this->requestAt = $requestAt;

        return $this;
    }

    public function getRequestAt(): ?\DateTimeInterface
    {
        return $this->requestAt;
    }
}
