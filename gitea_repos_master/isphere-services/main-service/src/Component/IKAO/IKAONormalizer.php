<?php

declare(strict_types=1);

namespace App\Component\IKAO;

class IKAONormalizer
{
    private const MAP = [
        'а' => 'a',
        'б' => 'b',
        'в' => 'v',
        'г' => 'g',
        'д' => 'd',
        'е' => 'e',
        'ё' => 'e',
        'ж' => 'zh',
        'з' => 'z',
        'и' => 'i',
        'й' => 'i',
        'к' => 'k',
        'л' => 'l',
        'м' => 'm',
        'н' => 'n',
        'о' => 'o',
        'п' => 'p',
        'р' => 'r',
        'с' => 's',
        'т' => 't',
        'у' => 'u',
        'ф' => 'f',
        'х' => 'kh',
        'ц' => 'ts',
        'ч' => 'ch',
        'ш' => 'sh',
        'щ' => 'shch',
        'ъ' => 'ie',
        'ы' => 'y',
        'ь' => '',
        'э' => 'e',
        'ю' => 'iu',
        'я' => 'ia',
    ];

    public static function denormalize(mixed $data, string $type = null, string $format = null, array $context = []): string
    {
        return \str_replace(\array_values(self::MAP), \array_keys(self::MAP), \mb_strtolower($data));
    }

    public static function normalize(mixed $object, string $format = null, array $context = []): string
    {
        return \str_replace(\array_keys(self::MAP), \array_values(self::MAP), \mb_strtolower($object));
    }

    public static function supportsDenormalization(mixed $data, string $type = null, string $format = null, array $context = []): bool
    {
        return \is_string($data) && \preg_match('~[a-z ]+~i', $data);
    }

    public static function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return \is_string($data) && \preg_match('~[а-яё ]+~ui', $data);
    }
}
