<?php

declare(strict_types=1);

namespace App\Model;

use App\Form\Type\FormatType;
use App\Form\Type\RussianRegionType;
use App\Validator\Constraint\INN;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class Check
{
    private ?string $lastName = null;

    private ?string $firstName = null;

    private ?string $patronymic = null;

    private ?\DateTimeInterface $date = null;

    private ?string $passportSeries = null;

    private ?string $passportNumber = null;

    private ?\DateTimeInterface $issueDate = null;

    #[INN]
    private ?string $inn = null;

    private ?string $snils = null;

    private ?string $driverNumber = null;

    private ?\DateTimeInterface $driverDate = null;

    private ?string $mobilePhone = null;

    private ?string $homePhone = null;

    private ?string $workPhone = null;

    private ?string $additionalPhone = null;

    #[Email]
    private ?string $email = null;

    #[Email]
    private ?string $additionalEmail = null;

    private ?string $skype = null;

    #[Choice(choices: RussianRegionType::DEFAULT_CHOICES)]
    private ?string $regionId = null;

    private ?\DateTimeInterface $reqdate = null;

    #[NotNull]
    #[Count(min: 1)]
    private ?array $sources = null;

    private ?array $rules = null;

    private ?bool $recursive = null;

    private ?bool $async = null;

    #[NotBlank]
    #[Choice(choices: FormatType::DEFAULT_CHOICES)]
    private ?string $format = null;

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
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

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
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

    public function setIssueDate(?\DateTimeInterface $issueDate): self
    {
        $this->issueDate = $issueDate;

        return $this;
    }

    public function getIssueDate(): ?\DateTimeInterface
    {
        return $this->issueDate;
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

    public function setMobilePhone(?string $mobilePhone): self
    {
        $this->mobilePhone = $mobilePhone;

        return $this;
    }

    public function getMobilePhone(): ?string
    {
        return $this->mobilePhone;
    }

    public function setHomePhone(?string $homePhone): self
    {
        $this->homePhone = $homePhone;

        return $this;
    }

    public function getHomePhone(): ?string
    {
        return $this->homePhone;
    }

    public function setWorkPhone(?string $workPhone): self
    {
        $this->workPhone = $workPhone;

        return $this;
    }

    public function getWorkPhone(): ?string
    {
        return $this->workPhone;
    }

    public function setAdditionalPhone(?string $additionalPhone): self
    {
        $this->additionalPhone = $additionalPhone;

        return $this;
    }

    public function getAdditionalPhone(): ?string
    {
        return $this->additionalPhone;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setAdditionalEmail(?string $additionalEmail): self
    {
        $this->additionalEmail = $additionalEmail;

        return $this;
    }

    public function getAdditionalEmail(): ?string
    {
        return $this->additionalEmail;
    }

    public function setSkype(?string $skype): self
    {
        $this->skype = $skype;

        return $this;
    }

    public function getSkype(): ?string
    {
        return $this->skype;
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

    public function setReqdate(?\DateTimeInterface $reqdate): self
    {
        $this->reqdate = $reqdate;

        return $this;
    }

    public function getReqdate(): ?\DateTimeInterface
    {
        return $this->reqdate;
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

    public function setRules(?array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    public function getRules(): ?array
    {
        return $this->rules;
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
