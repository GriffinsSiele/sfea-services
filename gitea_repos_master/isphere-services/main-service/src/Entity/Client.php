<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'Client')]
class Client
{
    #[Column(type: Types::INTEGER)]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[Column(name: 'Code', length: 50)]
    private ?string $code = null;

    #[Column(name: 'Name', length: 50)]
    private ?string $name = null;

    #[Column(name: 'OfficialName', length: 250, nullable: true)]
    private ?string $officialName = null;

    #[Column(name: 'FullName', length: 250, nullable: true)]
    private ?string $fullName = null;

    #[Column(name: 'INN', type: Types::BIGINT, nullable: true)]
    private ?int $inn = null;

    #[Column(name: 'OGRN', type: Types::BIGINT, nullable: true)]
    private ?int $ogrn = null;

    #[Column(name: 'KPP', type: Types::INTEGER, nullable: true)]
    private ?int $kpp = null;

    #[Column(name: 'Address', length: 1024, nullable: true)]
    private ?string $address = null;

    #[Column(name: 'Phone', length: 250, nullable: true)]
    private ?string $phone = null;

    #[Column(name: 'Email', length: 250, nullable: true)]
    private ?string $email = null;

    #[Column(name: 'ContactName', length: 250, nullable: true)]
    private ?string $contactName = null;

    #[Column(name: 'BIK', length: 10, nullable: true)]
    private ?string $bik = null;

    #[Column(name: 'Bank', length: 128, nullable: true)]
    private ?string $bank = null;

    #[Column(name: 'BankAccount', length: 20, nullable: true)]
    private ?string $bankAccount = null;

    #[Column(name: 'Status', type: Types::INTEGER, nullable: true)]
    private ?int $status = null;

    #[Column(name: 'TariffId', type: Types::INTEGER, nullable: true)]
    private ?int $tariffId = null;

    #[Column(name: 'ContractNum', length: 50, nullable: true)]
    private ?string $contractNum = null;

    #[Column(name: 'ContractStartDate', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $contractStartDate = null;

    #[Column(name: 'ContractStopDate', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $contractStopDate = null;

    #[Column(name: 'MasterUserId', type: Types::INTEGER, nullable: true)]
    private ?int $masterUserId = null;

    #[Column(name: 'MessageId', type: Types::INTEGER, nullable: true)]
    private ?int $messageId = null;

    #[ManyToOne(targetEntity: Message::class)]
    #[JoinColumn(name: 'MessageId')]
    private ?Message $message = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
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

    public function setOfficialName(?string $officialName): self
    {
        $this->officialName = $officialName;

        return $this;
    }

    public function getOfficialName(): ?string
    {
        return $this->officialName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setInn(?int $inn): self
    {
        $this->inn = $inn;

        return $this;
    }

    public function getInn(): ?int
    {
        return $this->inn;
    }

    public function setOgrn(?int $ogrn): self
    {
        $this->ogrn = $ogrn;

        return $this;
    }

    public function getOgrn(): ?int
    {
        return $this->ogrn;
    }

    public function setKpp(?int $kpp): self
    {
        $this->kpp = $kpp;

        return $this;
    }

    public function getKpp(): ?int
    {
        return $this->kpp;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
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

    public function setContactName(?string $contactName): self
    {
        $this->contactName = $contactName;

        return $this;
    }

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setBik(?string $bik): self
    {
        $this->bik = $bik;

        return $this;
    }

    public function getBik(): ?string
    {
        return $this->bik;
    }

    public function setBank(?string $bank): self
    {
        $this->bank = $bank;

        return $this;
    }

    public function getBank(): ?string
    {
        return $this->bank;
    }

    public function setBankAccount(?string $bankAccount): self
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }

    public function getBankAccount(): ?string
    {
        return $this->bankAccount;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setTariffId(?int $tariffId): self
    {
        $this->tariffId = $tariffId;

        return $this;
    }

    public function getTariffId(): ?int
    {
        return $this->tariffId;
    }

    public function setContractNum(?string $contractNum): self
    {
        $this->contractNum = $contractNum;

        return $this;
    }

    public function getContractNum(): ?string
    {
        return $this->contractNum;
    }

    public function setContractStartDate(?\DateTimeInterface $contractStartDate): self
    {
        $this->contractStartDate = $contractStartDate;

        return $this;
    }

    public function getContractStartDate(): ?\DateTimeInterface
    {
        return $this->contractStartDate;
    }

    public function setContractStopDate(?\DateTimeInterface $contractStopDate): self
    {
        $this->contractStopDate = $contractStopDate;

        return $this;
    }

    public function getContractStopDate(): ?\DateTimeInterface
    {
        return $this->contractStopDate;
    }

    public function setMasterUserId(?int $masterUserId): self
    {
        $this->masterUserId = $masterUserId;

        return $this;
    }

    public function getMasterUserId(): ?int
    {
        return $this->masterUserId;
    }

    public function setMessageId(?int $messageId): self
    {
        $this->messageId = $messageId;

        return $this;
    }

    public function getMessageId(): ?int
    {
        return $this->messageId;
    }

    public function setMessage(?Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }
}
