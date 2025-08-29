<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FieldRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: FieldRepository::class)]
#[Table(name: 'Field')]
class Field
{
    #[Column(type: Types::INTEGER)]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $sourceName = null;

    #[Column(name: 'checktype', type: Types::STRING, length: 50, nullable: true)]
    private ?string $checkType = null;

    #[Column(type: Types::STRING, length: 20)]
    private ?string $name = null;

    #[Column(name: '`type`', type: Types::STRING, length: 20)]
    private ?string $type = null;

    #[Column(type: Types::STRING, length: 250)]
    private ?string $title = null;

    #[Column(type: Types::STRING, length: 250)]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setSourceName(?string $sourceName): self
    {
        $this->sourceName = $sourceName;

        return $this;
    }

    public function getSourceName(): ?string
    {
        return $this->sourceName;
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

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
