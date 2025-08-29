<?php

declare(strict_types=1);

namespace App\Model;

use App\Validator\ResponseGroups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;

class Source
{
    #[SerializedName('@code')]
    #[NotBlank(groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    private ?string $code = null;

    #[SerializedName('@checktype')]
    #[NotBlank(groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    private ?string $checkType = null;

    #[SerializedName('@request_id')]
    #[NotBlank(groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    private ?string $requestId = null;

    #[SerializedName('@process_time')]
    #[NotBlank(groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    #[GreaterThan(0, groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    private null|int|float $processTime = null;

    #[SerializedName('Name')]
    #[NotBlank(groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    private ?string $name = null;

    #[SerializedName('Title')]
    #[NotBlank(groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    private ?string $title = null;

    #[SerializedName('CheckTitle')]
    #[NotBlank(groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    private ?string $checkTitle = null;

    #[SerializedName('Request')]
    #[NotBlank(groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    private ?string $request = null;

    #[SerializedName('ResultsCount')]
    #[EqualTo(0, groups: [ResponseGroups::NOT_FOUND])]
    #[GreaterThan(0, groups: [ResponseGroups::FOUND])]
    private int $resultsCount = 0;

    #[SerializedName('Error')]
    #[Blank(groups: [ResponseGroups::FOUND, ResponseGroups::NOT_FOUND])]
    private ?string $error = null;

    /**
     * @var Record[]|null
     */
    #[SerializedName('Record')]
    #[Count(0, groups: [ResponseGroups::NOT_FOUND])]
    #[Count(min: 1, groups: [ResponseGroups::FOUND])]
    #[Valid]
    private array $records = [];

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
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

    public function setRequestId(?string $requestId): self
    {
        $this->requestId = $requestId;

        return $this;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function setProcessTime(float|int|null $processTime): self
    {
        $this->processTime = $processTime;

        return $this;
    }

    public function getProcessTime(): float|int|null
    {
        return $this->processTime;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setCheckTitle(?string $checkTitle): self
    {
        $this->checkTitle = $checkTitle;

        return $this;
    }

    public function getCheckTitle(): ?string
    {
        return $this->checkTitle;
    }

    public function setRequest(?string $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function getRequest(): ?string
    {
        return $this->request;
    }

    public function setResultsCount(int $resultsCount): self
    {
        $this->resultsCount = $resultsCount;

        return $this;
    }

    public function getResultsCount(): int
    {
        return $this->resultsCount;
    }

    public function setError(?string $error): self
    {
        $this->error = $error;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setRecords(array $records): self
    {
        $this->records = $records;

        return $this;
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function getRecordsCount(): int
    {
        return \count($this->records);
    }
}
