<?php

declare(strict_types=1);

namespace App\Model\Fns;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Uid\Uuid;

class DebTamDocument
{
    #[SerializedName('@ИдДок')]
    private ?Uuid $id = null;

    #[SerializedName('@ДатаДок')]
    private ?\DateTimeInterface $createdAt = null;

    #[SerializedName('@ДатаСост')]
    private ?\DateTimeInterface $updatedAt = null;

    #[SerializedName('СведНП')]
    private ?Organization $subject = null;

    /**
     * @var Underpayment[]|null
     */
    #[SerializedName('СведНедоим')]
    private ?array $underpayments = null;

    public function setId(?Uuid $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
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

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setSubject(?Organization $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getSubject(): ?Organization
    {
        return $this->subject;
    }

    public function setUnderpayments(?array $underpayments): self
    {
        $this->underpayments = $underpayments;

        return $this;
    }

    public function getUnderpayments(): ?array
    {
        return $this->underpayments;
    }
}
