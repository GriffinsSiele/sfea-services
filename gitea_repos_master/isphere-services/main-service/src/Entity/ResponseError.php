<?php

declare(strict_types=1);

namespace App\Entity;

use App\Contract\DuplicateStatsEntityInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'ResponseError')]
class ResponseError implements DuplicateStatsEntityInterface
{
    #[Column(type: Types::INTEGER)]
    #[Id]
    private ?int $responseId = null;

    #[Column(length: 20, nullable: true)]
    private ?string $code = null;

    #[Column(type: Types::TEXT)]
    private ?string $text = null;

    public function setResponseId(?int $responseId): self
    {
        $this->responseId = $responseId;

        return $this;
    }

    public function getResponseId(): ?int
    {
        return $this->responseId;
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

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }
}
