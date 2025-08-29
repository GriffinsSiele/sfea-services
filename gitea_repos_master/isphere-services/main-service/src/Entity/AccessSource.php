<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'AccessSource')]
class AccessSource
{
    #[Column(type: Types::INTEGER)]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[Column(name: 'Level', type: Types::INTEGER)]
    private ?int $level = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $sourceId = null;

    #[Column(length: 50)]
    private ?string $sourceName = null;

    #[Column(type: Types::INTEGER, options: ['default' => 0])]
    private ?int $allowed = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setLevel(?int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setSourceId(?int $sourceId): self
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
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

    public function setAllowed(?int $allowed): self
    {
        $this->allowed = $allowed;

        return $this;
    }

    public function getAllowed(): ?int
    {
        return $this->allowed;
    }
}
