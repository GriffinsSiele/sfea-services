<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints\NotBlank;

class Field
{
    #[SerializedName('FieldType')]
    #[NotBlank]
    private ?string $type = null;

    #[SerializedName('FieldName')]
    #[NotBlank]
    private ?string $name = null;

    #[SerializedName('FieldTitle')]
    #[NotBlank]
    private ?string $title = null;

    #[SerializedName('FieldDescription')]
    #[NotBlank]
    private ?string $description = null;

    #[SerializedName('FieldValue')]
    #[NotBlank]
    private ?string $value = null;

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
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

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
