<?php

declare(strict_types=1);

namespace App\Model;

use App\Validator\ResponseGroups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;

class Response
{
    public const STATUS_FAILURE = -1;
    public const STATUS_IN_PROGRESS = 0;
    public const STATUS_COMPLETED = 1;

    public const ALL_STATUSES = [
        self::STATUS_FAILURE,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
    ];

    #[SerializedName('@id')]
    #[NotBlank(groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    #[GreaterThan(0, groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    private ?int $id = null;

    #[SerializedName('@status')]
    #[NotBlank(groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    #[Choice(choices: self::ALL_STATUSES, groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    private ?int $status = null;

    #[SerializedName('@datetime')]
    #[NotBlank(groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    private ?\DateTimeInterface $dateTime = null;

    #[SerializedName('@result')]
    #[NotBlank(groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    private ?string $result = null;

    #[SerializedName('@view')]
    #[NotBlank(groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    private ?string $view = null;

    /**
     * @var Source[]|null
     */
    #[SerializedName('Source')]
    #[NotNull(groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    #[Count(min: 1, groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    #[Valid]
    private ?array $sources = null;

    /**
     * @var Request[]|null
     */
    #[SerializedName('Request')]
    private ?array $requests = null;

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function setDateTime(?\DateTimeInterface $dateTime): self
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    public function getDateTime(): ?\DateTimeInterface
    {
        return $this->dateTime;
    }

    public function setResult(?string $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setView(?string $view): self
    {
        $this->view = $view;

        return $this;
    }

    public function getView(): ?string
    {
        return $this->view;
    }

    public function setSources(?array $sources): self
    {
        $this->sources = $sources;

        return $this;
    }

    public function addSource(Source $source): self
    {
        $this->sources ??= $source;
        $this->sources[] = $source;

        return $this;
    }

    public function getSources(): ?array
    {
        return $this->sources;
    }

    public function getSourcesCount(): int
    {
        if (null === $this->sources) {
            return 0;
        }

        return \count($this->sources);
    }

    public function hasSourceByCheckType(string $checkType): bool
    {
        foreach ($this->sources ?? [] as $source) {
            if ($source->getCheckType() === $checkType) {
                return true;
            }
        }

        return false;
    }

    public function getSourceByCheckType(string $checkType): ?Source
    {
        foreach ($this->sources ?? [] as $source) {
            if ($source->getCheckType() === $checkType) {
                return $source;
            }
        }

        return null;
    }

    public function setRequests(?array $requests): self
    {
        $this->requests = $requests;

        return $this;
    }

    public function getRequests(): ?array
    {
        return $this->requests;
    }
}
