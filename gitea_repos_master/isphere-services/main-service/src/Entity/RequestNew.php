<?php

declare(strict_types=1);

namespace App\Entity;

use App\Contract\DuplicateStatsEntityInterface;
use App\Repository\RequestNewRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: RequestNewRepository::class)]
#[Table(name: 'RequestNew')]
class RequestNew implements DuplicateStatsEntityInterface
{
    #[Column(type: Types::INTEGER)]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ManyToOne(targetEntity: SystemUser::class)]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'Id')]
    private ?SystemUser $user = null;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    public ?\DateTimeInterface $createdAt = null;

    #[Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    public ?\DateTimeInterface $createdDate = null;

    #[Column(type: Types::INTEGER)]
    public ?int $userId = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    public ?int $clientId = null;

    #[Column(length: 20, nullable: true)]
    public ?string $ip = null;

    #[Column(length: 100, nullable: true)]
    public ?string $externalId = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    public ?int $channel = null;

    #[Column(name: '`type`', length: 20, nullable: true)]
    public ?string $type = null;

    #[Column(name: '`recursive`', type: Types::INTEGER, nullable: true)]
    public ?int $recursive = null;

    #[Column(type: Types::INTEGER, options: ['default' => 0])]
    public ?int $status = null;

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?\DateTimeInterface $processedAt = null;

    #[OneToMany(mappedBy: 'request', targetEntity: ResponseNew::class)]
    private ?Collection $responses = null;

    public function __construct()
    {
        $this->responses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUser(?SystemUser $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?SystemUser
    {
        return $this->user;
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

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setChannel(?int $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function getChannel(): ?int
    {
        return $this->channel;
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

    public function setRecursive(?int $recursive): self
    {
        $this->recursive = $recursive;

        return $this;
    }

    public function getRecursive(): ?int
    {
        return $this->recursive;
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

    public function setProcessedAt(?\DateTimeInterface $processedAt): self
    {
        $this->processedAt = $processedAt;

        return $this;
    }

    public function getProcessedAt(): ?\DateTimeInterface
    {
        return $this->processedAt;
    }

    public function setResponses(?Collection $responses): self
    {
        $this->responses = $responses;

        return $this;
    }

    public function addResponse(ResponseNew $response): self
    {
        $this->responses->add($response);

        return $this;
    }

    public function removeResponse(ResponseNew $response): self
    {
        $this->responses->removeElement($response);

        return $this;
    }

    /**
     * @return Collection|ResponseNew[]|null
     */
    public function getResponses(): ?Collection
    {
        return $this->responses;
    }
}
