<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\DBAL\Type\RowsType;
use App\Doctrine\DBAL\Type\ScalarDefinitionListType;
use App\Model\Scalar as AppScalar;
use App\Model\ScalarDefinition;
use App\Repository\BulkRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[Entity(repositoryClass: BulkRepository::class)]
#[Table(name: 'Bulk')]
class Bulk
{
    public const STATUS_IN_PROGRESS = 0;
    public const STATUS_BEFORE_PROGRESS = 100;
    public const STATUS_COMPLETED = 1;
    public const STATUS_WAIT = 2;
    public const STATUS_NOT_COMPLETED = 3;
    public const STATUS_WAIT_FOR_CONFIRMATION = 20;
    public const STATUS_WAIT_CONFIRMED = 21;
    public const STATUS_GENERATING_FILE = 101;

    #[Column(type: Types::INTEGER)]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    #[Groups(['Public'])]
    private ?int $id = null;

    #[Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['Public'])]
    private ?\DateTimeInterface $createdAt = null;

    #[Column(name: 'created_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdDate = null;

    #[ManyToOne(targetEntity: SystemUser::class)]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'Id')]
    private ?SystemUser $user = null;

    #[Column(length: 20, nullable: true)]
    private ?string $ip = null;

    #[Column(length: 1024)]
    private ?string $filename = null;

    #[Column(length: 1024, nullable: true)]
    private ?string $resultFilename = null;

    #[Column(length: 1024, nullable: true)]
    private ?string $sources = null;

    #[Column(length: 1024, nullable: true)]
    private ?string $description = null;

    #[Column(name: '`recursive`', type: Types::INTEGER, nullable: true)]
    private ?int $recursive = null;

    #[Column(type: Types::INTEGER)]
    #[Groups(['Public'])]
    private ?int $status = null;

    #[Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $processedAt = null;

    #[Column(length: 1024, nullable: true)]
    private ?string $titles = null;

    #[Column(name: 'total_rows', type: Types::INTEGER, nullable: true)]
    private ?int $totalRows = null;

    #[Column(name: 'processed_rows', type: Types::INTEGER, nullable: true)]
    private ?int $processedRows = null;

    #[Column(name: 'success_rows', type: Types::INTEGER, nullable: true)]
    private ?int $successRows = null;

    #[Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[Column(type: Types::TEXT, nullable: true)]
    private ?string $resultsNote = null;

    #[Column(type: ScalarDefinitionListType::NAME, nullable: true)]
    private ?Collection $definitions = null;

    #[Column(name: '`rows`', type: RowsType::NAME, nullable: true)]
    private ?Collection $rows = null;

    #[ManyToMany(targetEntity: RequestNew::class)]
    private ?Collection $requests = null;

    public function __construct()
    {
        $this->definitions = new ArrayCollection();
        $this->rows = new ArrayCollection();
        $this->requests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->createdDate;
    }

    public function setCreatedDate(?\DateTimeInterface $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    public function getUser(): ?SystemUser
    {
        return $this->user;
    }

    public function setUser(?SystemUser $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function setResultFilename(?string $resultFilename): self
    {
        $this->resultFilename = $resultFilename;

        return $this;
    }

    public function getResultFilename(): ?string
    {
        return $this->resultFilename;
    }

    public function getSources(): ?string
    {
        return $this->sources;
    }

    #[Ignore]
    public function getSourcesAsArray(): array
    {
        if (null === $this->sources) {
            return [];
        }

        return \explode(',', $this->sources);
    }

    public function setSources(?string $sources): self
    {
        $this->sources = $sources;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getRecursive(): ?int
    {
        return $this->recursive;
    }

    public function setRecursive(?int $recursive): self
    {
        $this->recursive = $recursive;

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

    public function getProcessedAt(): ?\DateTimeInterface
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeInterface $processedAt): self
    {
        $this->processedAt = $processedAt;

        return $this;
    }

    public function getTitles(): ?string
    {
        return $this->titles;
    }

    public function setTitles(?string $titles): self
    {
        $this->titles = $titles;

        return $this;
    }

    public function getTotalRows(): ?int
    {
        return $this->totalRows;
    }

    public function setTotalRows(?int $totalRows): self
    {
        $this->totalRows = $totalRows;

        return $this;
    }

    public function getProcessedRows(): ?int
    {
        return $this->processedRows;
    }

    public function setProcessedRows(?int $processedRows): self
    {
        $this->processedRows = $processedRows;

        return $this;
    }

    public function getSuccessRows(): ?int
    {
        return $this->successRows;
    }

    public function setSuccessRows(?int $successRows): self
    {
        $this->successRows = $successRows;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getResultsNote(): ?string
    {
        return $this->resultsNote;
    }

    public function setResultsNote(?string $resultsNote): self
    {
        $this->resultsNote = $resultsNote;

        return $this;
    }

    public function setDefinitions(?Collection $definitions): self
    {
        $this->definitions = $definitions;

        return $this;
    }

    public function addDefinition(ScalarDefinition $definition): self
    {
        $this->definitions->add($definition);

        return $this;
    }

    public function removeDefinition(ScalarDefinition $definition): self
    {
        $this->definitions->removeElement($definition);

        return $this;
    }

    public function getDefinitions(): ?Collection
    {
        return $this->definitions;
    }

    /**
     * @return ScalarDefinition[]|null
     */
    #[SerializedName('definitions')]
    #[Groups(['Public'])]
    public function getDefinitionsRepresentative(): ?array
    {
        if (null === $this->definitions) {
            return null;
        }

        $iterator = $this->definitions->getIterator();
        $iterator->uasort(static fn (ScalarDefinition $a, ScalarDefinition $b): int => $a->getNumber() <=> $b->getNumber());

        return \iterator_to_array($iterator, false);
    }

    #[Ignore]
    public function hasAtLeastOneIdentifiedDefinition(): bool
    {
        if (null === $this->definitions) {
            return false;
        }

        /** @var ScalarDefinition $definition */
        foreach ($this->getDefinitions() as $definition) {
            if ($definition->isIdentifier()) {
                return true;
            }
        }

        return false;
    }

    public function setRows(?Collection $rows): self
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * @return array<AppScalar[]>|null
     */
    public function getRows(): ?Collection
    {
        return $this->rows;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setRequests(?Collection $requests): self
    {
        $this->requests = $requests;

        return $this;
    }

    public function addRequest(RequestNew $requestNew): self
    {
        $this->requests->add($requestNew);

        return $this;
    }

    public function removeRequest(RequestNew $requestNew): self
    {
        $this->requests->removeElement($requestNew);

        return $this;
    }

    /**
     * @return Collection|RequestNew[]|null
     */
    public function getRequests(): ?Collection
    {
        return $this->requests;
    }
}
