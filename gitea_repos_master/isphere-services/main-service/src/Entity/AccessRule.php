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
#[Table(name: 'AccessRule')]
class AccessRule
{
    #[Column(type: Types::INTEGER)]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[Column(name: 'Level', type: Types::INTEGER)]
    private ?int $level = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $ruleId = null;

    #[Column(length: 50)]
    private ?string $ruleName = null;

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

    public function setRuleId(?int $ruleId): self
    {
        $this->ruleId = $ruleId;

        return $this;
    }

    public function getRuleId(): ?int
    {
        return $this->ruleId;
    }

    public function setRuleName(?string $ruleName): self
    {
        $this->ruleName = $ruleName;

        return $this;
    }

    public function getRuleName(): ?string
    {
        return $this->ruleName;
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
