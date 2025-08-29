<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\SystemUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SourceListType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'check_sources',
            ])
            ->setDefaults([
                'choice_loader' => function (Options $options): ?ChoiceLoaderInterface {
                    $user = $this->security->getUser();
                    if (!$user instanceof SystemUser) {
                        return null;
                    }

                    $checkSources = $options['check_sources'];
                    $choices = [];

                    foreach ($checkSources as $k => $s) {
                        if (!$user->hasAccessSourceBySourceName($k)) {
                            continue;
                        }

                        $choices[$s[0]] = $k;
                    }

                    return ChoiceList::loader(
                        $this,
                        new CallbackChoiceLoader(fn () => $choices),
                    );
                },
                'expanded' => true,
                'multiple' => true,
            ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
