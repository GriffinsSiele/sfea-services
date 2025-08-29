<?php

declare(strict_types=1);

namespace App\Model;

use App\Contract\ScalarType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class ScalarDefinition
{
    public function __construct(
        #[Groups(['Default', 'Public'])]
        #[NotNull]
        private string|ScalarType $type,

        #[Groups(['Default', 'Public'])]
        #[NotBlank]
        private readonly int $number,

        #[Groups(['Default', 'Public'])]
        private bool $unique = false,

        #[Groups(['Default', 'Public'])]
        private bool $identifier = false,
    ) {
    }

    public function setType(ScalarType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ScalarType
    {
        return $this->type;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setUnique(bool $unique): self
    {
        $this->unique = $unique;

        return $this;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function setIdentifier(bool $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function isIdentifier(): bool
    {
        return $this->identifier;
    }
}
