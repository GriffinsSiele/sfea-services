<?php

declare(strict_types=1);

namespace App\Tests\Component\IKAO;

use App\Component\IKAO\IKAONormalizer;
use PHPUnit\Framework\TestCase;

class IKAONormalizerTest extends TestCase
{
    public function testDenormalizer(): void
    {
        $denormalizer = new IKAONormalizer();
        $denormalized = $denormalizer->denormalize('ivan petrovich ivanov');

        self::assertEquals('иван петрович иванов', $denormalized);
    }

    public function testNormalizer(): void
    {
        $normalizer = new IKAONormalizer();
        $normalized = $normalizer->normalize('Съешь ещё этих мягких французских булок, да выпей же чаю');

        self::assertEquals('sieesh eshche etikh miagkikh frantsuzskikh bulok, da vypei zhe chaiu', $normalized);
    }
}
