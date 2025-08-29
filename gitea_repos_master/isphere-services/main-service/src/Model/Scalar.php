<?php

declare(strict_types=1);

namespace App\Model;

use App\Contract\ScalarType;
use Symfony\Component\Serializer\Annotation\Groups;

class Scalar
{
    #[Groups('Default')]
    private ScalarType $type = ScalarType::UNKNOWN;

    #[Groups('Default')]
    private bool $guessed = false;

    public function __construct(
        #[Groups('Default')]
        private readonly mixed $value,
    ) {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return empty($this->getValue());
    }

    public function setType(?ScalarType $type): self
    {
        $this->type = $type ?? ScalarType::UNKNOWN;

        return $this;
    }

    public function getType(): ScalarType
    {
        return $this->type;
    }

    public function setGuessed(bool $guessed): self
    {
        $this->guessed = $guessed;

        return $this;
    }

    public function isGuessed(): bool
    {
        return $this->guessed;
    }
}
