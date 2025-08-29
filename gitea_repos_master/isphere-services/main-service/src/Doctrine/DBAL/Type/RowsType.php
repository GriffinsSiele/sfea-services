<?php

declare(strict_types=1);

namespace App\Doctrine\DBAL\Type;

use App\Model\FileArrayCollection;
use App\Model\Scalar;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class RowsType extends AbstractSerializedType
{
    public const NAME = 'rows';

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof FileArrayCollection) {
            $path = \rtrim($this->getParameter('app.bulk_path'), '/');
            $filepath = $path.'/'.$value->getFilename();
            $binary = \gzdeflate(\serialize($value->getValues()), 9);

            \file_put_contents($filepath, $binary);

            $value = ['__filename' => $value->getFilename()];
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if (\is_string($value)
            && \str_contains(\substr($value, 0, 16), '__filename')
        ) {
            $o = \json_decode($value, true, 512, \JSON_THROW_ON_ERROR);
            $path = \rtrim($this->getParameter('app.bulk_path'), '/');
            $filepath = $path.'/'.$o['__filename'];

            try {
                /* @noinspection UnserializeExploitsInspection */
                return new ArrayCollection(\unserialize(\gzinflate(\file_get_contents($filepath))));
            } catch (\Throwable) {
                return new ArrayCollection();
            }
        }

        $result = parent::convertToPHPValue($value, $platform);

        if (null === $result) {
            return new ArrayCollection();
        }

        return new ArrayCollection($result);
    }

    protected function getClassName(): string
    {
        return Scalar::class.'[][]';
    }
}
