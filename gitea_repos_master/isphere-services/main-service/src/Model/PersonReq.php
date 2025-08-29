<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Annotation\SerializedName;

class PersonReq
{
    #[SerializedName('paternal')]
    private ?string $surname = null;

    #[SerializedName('first')]
    private ?string $name = null;

    #[SerializedName('middle')]
    private ?string $patronymic = null;

    #[SerializedName('birthDt')]
    private ?\DateTimeInterface $birthday = null;

    #[SerializedName('placeOfBirth')]
    private ?string $birthplace = null;

    #[SerializedName('driver_number')]
    private ?string $driverLicenseNumber = null;

    #[SerializedName('driver_date')]
    private ?\DateTimeInterface $driverLicenseIssueAt = null;

    #[SerializedName('passport_series')]
    private ?string $passportSeries = null;

    #[SerializedName('passport_number')]
    private ?string $passportNumber = null;

    #[SerializedName('issueDate')]
    private ?\DateTimeInterface $passportIssueAt = null;

    #[SerializedName('issueAuthority')]
    private ?string $passportIssuer = null;

    #[SerializedName('inn')]
    private ?string $inn = null;

    #[SerializedName('snils')]
    private ?string $snils = null;

    #[SerializedName('region_id')]
    private ?string $regionId = null;

    #[SerializedName('reqdate')]
    private ?\DateTimeInterface $requestAt = null;

    #[SerializedName('homeaddress')]
    private ?string $addressHome = null;

    #[SerializedName('homeaddressArr')]
    private ?string $addressHomeArr = null;

    #[SerializedName('regaddress')]
    private ?string $addressRegistration = null;

    #[SerializedName('regaddressArr')]
    private ?string $addressRegistrationArr = null;

    public function setSurname(?string $surname): self
    {
        $this->surname = $surname;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setPatronymic(?string $patronymic): self
    {
        $this->patronymic = $patronymic;

        return $this;
    }

    public function getPatronymic(): ?string
    {
        return $this->patronymic;
    }

    public function setBirthday(?\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthplace(?string $birthplace): self
    {
        $this->birthplace = $birthplace;

        return $this;
    }

    public function getBirthplace(): ?string
    {
        return $this->birthplace;
    }

    public function setDriverLicenseNumber(?string $driverLicenseNumber): self
    {
        $this->driverLicenseNumber = $driverLicenseNumber;

        return $this;
    }

    public function getDriverLicenseNumber(): ?string
    {
        return $this->driverLicenseNumber;
    }

    public function setDriverLicenseIssueAt(?\DateTimeInterface $driverLicenseIssueAt): self
    {
        $this->driverLicenseIssueAt = $driverLicenseIssueAt;

        return $this;
    }

    public function getDriverLicenseIssueAt(): ?\DateTimeInterface
    {
        return $this->driverLicenseIssueAt;
    }

    public function setPassportSeries(?string $passportSeries): self
    {
        $this->passportSeries = $passportSeries;

        return $this;
    }

    public function getPassportSeries(): ?string
    {
        return $this->passportSeries;
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

    public function setPassportIssueAt(?\DateTimeInterface $passportIssueAt): self
    {
        $this->passportIssueAt = $passportIssueAt;

        return $this;
    }

    public function getPassportIssueAt(): ?\DateTimeInterface
    {
        return $this->passportIssueAt;
    }

    public function setPassportIssuer(?string $passportIssuer): self
    {
        $this->passportIssuer = $passportIssuer;

        return $this;
    }

    public function getPassportIssuer(): ?string
    {
        return $this->passportIssuer;
    }

    public function setInn(?string $inn): self
    {
        $this->inn = $inn;

        return $this;
    }

    public function getInn(): ?string
    {
        return $this->inn;
    }

    public function setSnils(?string $snils): self
    {
        $this->snils = $snils;

        return $this;
    }

    public function getSnils(): ?string
    {
        return $this->snils;
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

    public function setRequestAt(?\DateTimeInterface $requestAt): self
    {
        $this->requestAt = $requestAt;

        return $this;
    }

    public function getRequestAt(): ?\DateTimeInterface
    {
        return $this->requestAt;
    }

    public function setAddressHome(?string $addressHome): self
    {
        $this->addressHome = $addressHome;

        return $this;
    }

    public function getAddressHome(): ?string
    {
        return $this->addressHome;
    }

    public function setAddressHomeArr(?string $addressHomeArr): self
    {
        $this->addressHomeArr = $addressHomeArr;

        return $this;
    }

    public function getAddressHomeArr(): ?string
    {
        return $this->addressHomeArr;
    }

    public function setAddressRegistration(?string $addressRegistration): self
    {
        $this->addressRegistration = $addressRegistration;

        return $this;
    }

    public function getAddressRegistration(): ?string
    {
        return $this->addressRegistration;
    }

    public function setAddressRegistrationArr(?string $addressRegistrationArr): self
    {
        $this->addressRegistrationArr = $addressRegistrationArr;

        return $this;
    }

    public function getAddressRegistrationArr(): ?string
    {
        return $this->addressRegistrationArr;
    }
}
