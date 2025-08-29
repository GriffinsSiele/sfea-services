<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\SystemUser;
use App\Model\CheckOrg;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckOrgType extends AbstractType
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
            ->add('inn', TextType::class, [
                'label' => 'ИНН',
                'attr' => [
                    'data-imask' => true,
                    'data-imask-mask' => '0000000000',
                    'placeholder' => 'ИНН',
                    'autofocus' => true,
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
            'data_class' => CheckOrg::class,
        ]);
    }

    private function getCheckSources(): array
    {
        return [
            // Источники (название,выбран,рекурсивный,конец строки)
            '2gis' => ['2ГИС', 1, 1, 1],
            'egrul' => ['ЕГРЮЛ', 1, 0, 0],
            'fns' => ['ФНС', 1, 0, 0],
            //  'gks'=>array('Росстат',1,0,0),
            'bankrot' => ['Банкроты', 1, 0, 1],
            'cbr' => ['ЦБ РФ', 1, 0, 0],
            //  'terrorist'=>array('Террористы',1,0,0),
            //  'rz'=>array('Реестр залогов',1,0,0),
            'reestrzalogov' => ['Реестр залогов', 1, 0, 0],
            'rsa_org' => ['РСА КБМ', 0, 0, 1],
            'fssp' => ['ФССП', 1, 0, 0],
            //  'fsspapi'=>array('ФССП (API)',1,0,0),
            //  'fsspsite'=>array('ФССП (сайт)',1,0,0),
            'vestnik' => ['Вестник', 0, 0, 0],
            //  'fedresurs'=>array('Федресурс',1,0,0),
            //  'kad'=>array('Арбитражный суд',1,0,0),
            'zakupki' => ['Госзакупки', 1, 0, 1],
            //  'rkn'=>array('Роскомнадзор',1,0,0),
            //  'proverki'=>array('Проверки',1,0,1),
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
