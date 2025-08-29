<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OkatoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: OkatoRepository::class)]
#[Table(name: 'okato')]
class Okato
{
    #[Column(length: 2, options: ['comment' => 'Код региона'])]
    #[Id]
    private ?string $ter = null;

    #[Column(length: 3, options: ['comment' => 'Код района/города'])]
    #[Id]
    private ?string $kod1 = null;

    #[Column(length: 3, options: ['comment' => 'Код рабочего поселка/сельсовета'])]
    #[Id]
    private ?string $kod2 = null;

    #[Column(length: 3, options: ['comment' => 'Код сельского населенного пункта'])]
    #[Id]
    private ?string $kod3 = null;

    #[Column(length: 1, options: ['comment' => 'Код раздела'])]
    #[Id]
    private ?string $razdel = null;

    #[Column(length: 250, options: ['comment' => 'Наименование территории'])]
    private ?string $name1 = null;

    #[Column(length: 80, nullable: true, options: ['comment' => 'Дополнительная информация'])]
    private ?string $centrum = null;

    #[Column(length: 8000, nullable: true, options: ['comment' => 'Описание'])]
    private ?string $nomDescr = null;

    #[Column(type: Types::INTEGER, options: ['comment' => 'Номер изменения'])]
    private ?int $nomAkt = null;

    #[Column(type: Types::INTEGER, options: ['comment' => 'Тип изменения'])]
    private ?int $status = null;

    #[Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => 'Дата принятия'])]
    private ?\DateTimeInterface $dateUtv = null;

    #[Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => 'Дата введения'])]
    private ?\DateTimeInterface $dateVved = null;

    public function getTer(): ?string
    {
        return $this->ter;
    }

    public function setTer(?string $ter): self
    {
        $this->ter = $ter;

        return $this;
    }

    public function getKod1(): ?string
    {
        return $this->kod1;
    }

    public function setKod1(?string $kod1): self
    {
        $this->kod1 = $kod1;

        return $this;
    }

    public function getKod2(): ?string
    {
        return $this->kod2;
    }

    public function setKod2(?string $kod2): self
    {
        $this->kod2 = $kod2;

        return $this;
    }

    public function getKod3(): ?string
    {
        return $this->kod3;
    }

    public function setKod3(?string $kod3): self
    {
        $this->kod3 = $kod3;

        return $this;
    }

    public function getRazdel(): ?string
    {
        return $this->razdel;
    }

    public function setRazdel(?string $razdel): self
    {
        $this->razdel = $razdel;

        return $this;
    }

    public function getName1(): ?string
    {
        return $this->name1;
    }

    public function setName1(?string $name1): self
    {
        $this->name1 = $name1;

        return $this;
    }

    public function getCentrum(): ?string
    {
        return $this->centrum;
    }

    public function setCentrum(?string $centrum): self
    {
        $this->centrum = $centrum;

        return $this;
    }

    public function getNomDescr(): ?string
    {
        return $this->nomDescr;
    }

    public function setNomDescr(?string $nomDescr): self
    {
        $this->nomDescr = $nomDescr;

        return $this;
    }

    public function getNomAkt(): ?int
    {
        return $this->nomAkt;
    }

    public function setNomAkt(?int $nomAkt): self
    {
        $this->nomAkt = $nomAkt;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getDateUtv(): ?\DateTimeInterface
    {
        return $this->dateUtv;
    }

    public function setDateUtv(?\DateTimeInterface $dateUtv): self
    {
        $this->dateUtv = $dateUtv;

        return $this;
    }

    public function getDateVved(): ?\DateTimeInterface
    {
        return $this->dateVved;
    }

    public function setDateVved(?\DateTimeInterface $dateVved): self
    {
        $this->dateVved = $dateVved;

        return $this;
    }
}
