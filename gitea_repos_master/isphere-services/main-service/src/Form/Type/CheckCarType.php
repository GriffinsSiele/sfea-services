<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\SystemUser;
use App\Model\CheckCar;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckCarType extends AbstractType
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
            ->add('vin', TextType::class, [
                'label' => 'VIN',
                'required' => false,
                'attr' => [
                    'autofocus' => true,
                    'placeholder' => 'VIN',
                ],
                ...$rowAttr,
            ])
            ->add('bodyNumber', TextType::class, [
                'label' => 'Номер кузова',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Номер кузова',
                ],
                ...$rowAttr,
            ])
            ->add('chassis', TextType::class, [
                'label' => 'Номер шасси',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Номер шасси',
                ],
                ...$rowAttr,
            ])
            ->add('regNumber', TextType::class, [
                'label' => 'Гос.номер',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Гос.номер',
                ],
                ...$rowAttr,
            ])
            ->add('ctc', TextType::class, [
                'label' => 'Св-во о регистрации ТС',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Св-во о регистрации ТС',
                ],
                ...$rowAttr,
            ])
            ->add('pts', TextType::class, [
                'label' => 'Паспорт ТС',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Паспорт ТС',
                ],
                ...$rowAttr,
            ])
            ->add('osago', TextType::class, [
                'label' => 'Полис ОСАГО',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Полис ОСАГО',
                ],
                ...$rowAttr,
            ])
            ->add('reqDate', DateType::class, [
                'label' => 'Дата запроса в РСА',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'placeholder' => 'Дата запроса в РСА',
                ],
                ...$rowAttr,
            ])
            ->add('driverNumber', TextType::class, [
                'label' => 'Номер в/у',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Номер в/у',
                ],
                ...$rowAttr,
            ])
            ->add('driverDate', DateType::class, [
                'label' => 'Дата выдачи в/у',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'placeholder' => 'Дата выдачи в/у',
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
            'data_class' => CheckCar::class,
        ]);
    }

    private function getCheckSources(): array
    {
        return [
            'gibdd_history' => ['ГИБДД история', 1, 0, 0],
            'gibdd_aiusdtp' => ['ГИБДД дтп', 1, 0, 1],
            'gibdd_restricted' => ['ГИБДД ограничения', 1, 0, 0],
            'gibdd_wanted' => ['ГИБДД розыск', 1, 0, 1],
            'gibdd_diagnostic' => ['ГИБДД техосмотр', 1, 0, 0],
            'gibdd_fines' => ['ГИБДД штрафы', 0, 0, 1],
            'gibdd_driver' => ['ГИБДД права', 1, 0, 0],
            'rsa_policy' => ['РСА авто', 1, 0, 0],
            'rsa_bsostate' => ['РСА полис', 1, 0, 0],
            'rsa_kbm' => ['РСА КБМ', 1, 0, 1],
            'eaisto' => ['ЕАИСТО', 1, 0, 0],
            'avtokod' => ['Автокод', 1, 0, 0],
            'gisgmp' => ['ГИС ГМП', 1, 0, 1],
            //  'rz'=>array('Реестр залогов',1,0,0),
            'reestrzalogov' => ['Реестр залогов', 1, 0, 0],
            //  'avinfo'=>array('AvInfo',1,1,0)),
            //  'vin'=>array('Расшифровка VIN',1,1,0),
            'fssp' => ['ФССП', 1, 0, 1],
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
