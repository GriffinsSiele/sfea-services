<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\SystemUser;
use App\Model\CheckIP;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckIPType extends AbstractType
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
            ->add('ip', TextType::class, [
                'label' => 'IP-адрес',
                'attr' => [
                    'autofocus' => true,
                    'placeholder' => 'IP-адрес',
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
            'data_class' => CheckIP::class,
        ]);
    }

    private function getCheckSources(): array
    {
        return [
            'dns' => ['DNS', 1, 0, 0],
            'ipgeo' => ['IpGeo', 1, 0, 0],
            'sypexgeo' => ['SypexGeo', 1, 0, 1],
            'ripe' => ['RIPE', 1, 0, 0],
            'shodan' => ['Shodan', 1, 0, 0],
            'censys' => ['Censys', 1, 0, 1],
            //  'yamap'=>array('Яндекс.Карты',0,1,0),
            //  '2gis'=>array('2ГИС',0,1,0),
            //  'egrul'=>array('ЕГРЮЛ',0,1,1),
            //  'getcontact'=>array('GetContact',0,1,0),
            //  'truecaller'=>array('TrueCaller',0,1,0),
            //  'emt'=>array('EmobileTracker',0,1,1),
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
