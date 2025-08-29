<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\SystemUser;
use App\Model\CheckCard;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckCardType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof SystemUser) {
            return;
        }

        $rowAttr = [
            'row_attr' => [
                'class' => 'form-floating mb-3',
            ],
        ];

        $builder
            ->add('cardNumber', TextType::class, [
                'label' => 'Номер карты',
                'attr' => [
                    'autofocus' => true,
                    'placeholder' => 'Номер карты',
                ],
                ...$rowAttr,
            ])
            ->add('sources', SourceListType::class, [
                'label' => 'Источники',
                'required' => false,
                'attr' => [
                    'class' => 'inline',
                    'data-list-helper' => true,
                ],
                'check_sources' => $this->getCheckSources(),
                'data' => $this->getDefaultCheckSources(),
            ])
            ->add('async', CheckboxType::class, [
                'label' => 'Подгружать информацию по мере получения',
                'required' => false,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
                'data' => true,
            ])
            ->add('format', FormatType::class, [
                'label' => 'Формат ответа',
                'attr' => [
                    'class' => 'inline',
                ],
                'data' => 'html',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CheckCard::class,
        ]);
    }

    private function getCheckSources(): array
    {
        return [
            'sber' => ['Сбербанк', 1, 0, 1],
        ];
    }

    private function getDefaultCheckSources(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof SystemUser) {
            return [];
        }

        return \array_keys(
            \array_filter(
                $this->getCheckSources(),
                static fn (array $v, string $k) => 1 === $v[1]
                    && $user->hasAccessSourceBySourceName($k),
                \ARRAY_FILTER_USE_BOTH,
            )
        );
    }
}
