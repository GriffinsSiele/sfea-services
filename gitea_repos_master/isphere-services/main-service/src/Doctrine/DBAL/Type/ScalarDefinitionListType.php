<?php

declare(strict_types=1);

namespace App\Doctrine\DBAL\Type;

use App\Model\ScalarDefinition;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class ScalarDefinitionListType extends AbstractSerializedType
{
    public const NAME = 'scalar_definition_list';

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof ArrayCollection) {
            $value = $value->getValues();
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        $result = parent::convertToPHPValue($value, $platform);

        if (null === $result) {
            return new ArrayCollection();
        }

        return new ArrayCollection($result);
    }

    protected function getClassName(): string
    {
        return ScalarDefinition::class.'[]';
    }
}
