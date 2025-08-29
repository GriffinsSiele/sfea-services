<?php

declare(strict_types=1);

namespace App\Doctrine\DBAL\Type;

use App\Kernel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\BlobType;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractSerializedType extends BlobType
{
    public const NAME = 'serialized';

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof ArrayCollection) {
            $value = $value->getIterator();
        }

        return $this->getSerializer()->serialize($value, JsonEncoder::FORMAT, [
            'groups' => ['Default'],
        ]);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        return $this->getSerializer()->deserialize($value, $this->getClassName(), JsonEncoder::FORMAT, [
            'groups' => ['Default'],
        ]);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    abstract protected function getClassName(): string;

    protected function getParameter(string $key): mixed
    {
        return Kernel::getInstance()->getContainer()->getParameter($key);
    }

    private function getSerializer(): SerializerInterface
    {
        return Kernel::getInstance()->getContainer()->get('app.serializer');
    }
}
