<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NonStrictChoiceExtension extends AbstractExtension
{
    public function getFunctions(): iterable
    {
        yield new TwigFunction('non_strict_choice_value', [$this, 'nonStrictChoiceValue']);
    }

    /**
     * @param ChoiceView[] $choices
     */
    public function nonStrictChoiceValue(string $target, array $choices): ?string
    {
        foreach ($choices as $choice) {
            if (0 === \strcasecmp($target, $choice->value)
                || 0 === \strcasecmp($target, $choice->label)
            ) {
                return $choice->value;
            }
        }

        return null;
    }
}
