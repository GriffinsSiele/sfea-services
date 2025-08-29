<?php

declare(strict_types=1);

namespace App\Entity;

use App\Contract\DuplicateStatsEntityInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'ResponseNew')]
class ResponseNew implements DuplicateStatsEntityInterface
{
    #[Column(type: Types::INTEGER)]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ManyToOne(targetEntity: RequestNew::class, inversedBy: 'responses')]
    #[JoinColumn(name: 'request_id')]
    private ?RequestNew $request = null;

    #[Column(type: Types::INTEGER)]
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

    #[Column(name: 'checktype', length: 20, nullable: true)]
    private ?string $checkType = null;

    #[Column(length: 20, nullable: true)]
    private ?string $startParam = null;

    #[Column(type: Types::SMALLINT, length: 6, nullable: true)]
    private ?int $checkIndex = null;

    #[Column(type: Types::SMALLINT, length: 6, nullable: true)]
    private ?int $checkLevel = null;

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $processedAt = null;

    #[Column(type: Types::FLOAT, nullable: true)]
    private ?float $processTime = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $resultCount = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $resCode = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setRequest(?RequestNew $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function getRequest(): ?RequestNew
    {
        return $this->request;
    }

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

    public function setCheckType(?string $checkType): self
    {
        $this->checkType = $checkType;

        return $this;
    }

    public function getCheckType(): ?string
    {
        return $this->checkType;
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

    public function setCheckIndex(?int $checkIndex): self
    {
        $this->checkIndex = $checkIndex;

        return $this;
    }

    public function getCheckIndex(): ?int
    {
        return $this->checkIndex;
    }

    public function setCheckLevel(?int $checkLevel): self
    {
        $this->checkLevel = $checkLevel;

        return $this;
    }

    public function getCheckLevel(): ?int
    {
        return $this->checkLevel;
    }

    public function setProcessedAt(?\DateTimeInterface $processedAt): self
    {
        $this->processedAt = $processedAt;

        return $this;
    }

    public function getProcessedAt(): ?\DateTimeInterface
    {
        return $this->processedAt;
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

    public function setResultCount(?int $resultCount): self
    {
        $this->resultCount = $resultCount;

        return $this;
    }

    public function getResultCount(): ?int
    {
        return $this->resultCount;
    }

    public function setResCode(?int $resCode): self
    {
        $this->resCode = $resCode;

        return $this;
    }

    public function getResCode(): ?int
    {
        return $this->resCode;
    }
}
