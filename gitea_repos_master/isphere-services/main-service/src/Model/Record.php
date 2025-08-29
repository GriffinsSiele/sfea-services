<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;

class Record
{
    /**
     * @var Field[]|null
     */
    #[SerializedName('Field')]
    #[NotNull]
    #[Valid]
    private ?array $fields = null;

    public function setFields(?array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function getFields(): ?array
    {
        return $this->fields;
    }

    public function getFieldsCount(): int
    {
        if (null === $this->fields) {
            return 0;
        }

        return \count($this->fields);
    }

    public function getFieldNames(): array
    {
        $fieldNames = [];

        foreach ($this->fields ?? [] as $field) {
            $fieldNames[] = $field->getName();
        }

        return $fieldNames;
    }

    public function getFieldByName(string $name): ?Field
    {
        foreach ($this->fields ?? [] as $field) {
            if ($field->getName() === $name) {
                return $field;
            }
        }

        return null;
    }

    public function getFieldValueByName(string $name): mixed
    {
        return $this->getFieldByName($name)?->getValue();
    }

    public function hasFieldByName(string $name): bool
    {
        foreach ($this->fields ?? [] as $field) {
            if ($field->getName() === $name) {
                return true;
            }
        }

        return false;
    }
}
