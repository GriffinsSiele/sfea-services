<?php

declare(strict_types=1);

namespace App\Model\Fns;

use Symfony\Component\Serializer\Annotation\SerializedName;

class DebTam
{
    #[SerializedName('@ИдФайл')]
    private ?string $id = null;

    #[SerializedName('@ВерсФорм')]
    private ?float $formVersion = null;

    #[SerializedName('@ВерсПрог')]
    private ?float $appVersion = null;

    #[SerializedName('@ТипИнф')]
    private ?string $type = null;

    #[SerializedName('ИдОтпр')]
    private ?Sender $sender = null;

    /**
     * @var DebTamDocument[]|null
     */
    #[SerializedName('Документ')]
    private ?array $documents = null;

    #[SerializedName('@КолДок')]
    private ?int $documentsCount = null;

    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setFormVersion(?float $formVersion): self
    {
        $this->formVersion = $formVersion;

        return $this;
    }

    public function getFormVersion(): ?float
    {
        return $this->formVersion;
    }

    public function setAppVersion(?float $appVersion): self
    {
        $this->appVersion = $appVersion;

        return $this;
    }

    public function getAppVersion(): ?float
    {
        return $this->appVersion;
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

    public function setSender(?Sender $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getSender(): ?Sender
    {
        return $this->sender;
    }

    public function setDocuments(?array $documents): self
    {
        $this->documents = $documents;

        return $this;
    }

    public function getDocuments(): ?array
    {
        return $this->documents;
    }

    public function setDocumentsCount(?int $documentsCount): self
    {
        $this->documentsCount = $documentsCount;

        return $this;
    }

    public function getDocumentsCount(): ?int
    {
        return $this->documentsCount;
    }
}
