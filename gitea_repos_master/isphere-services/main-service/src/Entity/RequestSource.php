<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'RequestSource')]
class RequestSource
{
    #[Column(type: Types::INTEGER)]
    #[Id]
    private ?int $requestId = null;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeInterface $createdDate = null;

    #[Column(type: Types::INTEGER)]
    private ?int $userId = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $clientId = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $sourceId = null;

    #[Column(length: 20, nullable: true)]
    private ?string $sourceName = null;

    #[Column(length: 20, nullable: true)]
    private ?string $startParam = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $checkCount = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $successCount = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $foundCount = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $errorCount = null;

    #[Column(type: Types::FLOAT, nullable: true)]
    private ?float $processTime = null;

    public function setRequestId(?int $requestId): self
    {
        $this->requestId = $requestId;

        return $this;
    }

    public function getRequestId(): ?int
    {
        return $this->requestId;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedDate(?\DateTimeInterface $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->createdDate;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setClientId(?int $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getClientId(): ?int
    {
        return $this->clientId;
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

    public function setStartParam(?string $startParam): self
    {
        $this->startParam = $startParam;

        return $this;
    }

    public function getStartParam(): ?string
    {
        return $this->startParam;
    }

    public function setCheckCount(?int $checkCount): self
    {
        $this->checkCount = $checkCount;

        return $this;
    }

    public function getCheckCount(): ?int
    {
        return $this->checkCount;
    }

    public function setSuccessCount(?int $successCount): self
    {
        $this->successCount = $successCount;

        return $this;
    }

    public function getSuccessCount(): ?int
    {
        return $this->successCount;
    }

    public function setFoundCount(?int $foundCount): self
    {
        $this->foundCount = $foundCount;

        return $this;
    }

    public function getFoundCount(): ?int
    {
        return $this->foundCount;
    }

    public function setErrorCount(?int $errorCount): self
    {
        $this->errorCount = $errorCount;

        return $this;
    }

    public function getErrorCount(): ?int
    {
        return $this->errorCount;
    }

    public function setProcessTime(?float $processTime): self
    {
        $this->processTime = $processTime;

        return $this;
    }

    public function getProcessTime(): ?float
    {
        return $this->processTime;
    }
}
