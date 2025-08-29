<?php

declare(strict_types=1);

namespace App\Twig;

use App\Contract\ScalarType;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ScalarExtension extends AbstractExtension
{
    public function getFunctions(): iterable
    {
        yield new TwigFunction('all_scalar_types', [$this, 'allScalarTypes']);
    }

    public function allScalarTypes(): array
    {
        return ScalarType::cases();
    }
}
