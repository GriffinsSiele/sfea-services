<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\SystemUser;
use App\Model\CheckEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckEmailType extends AbstractType
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
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'autofocus' => true,
                    'placeholder' => 'Email',
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
            'data_class' => CheckEmail::class,
        ]);
    }

    private function getCheckSources(): array
    {
        return [
            'gosuslugi_email' => ['Госуслуги', 1, 0, 1],
            //  'people'=>array('Соцсети',1,0,0),
            'vk' => ['VK', 1, 1, 0],
            'ok' => ['OK', 1, 1, 0],
            'mailru' => ['Mail.Ru', 1, 1, 1],
            'twitter' => ['Twitter', 1, 1, 0],
            'facebook' => ['Facebook', 1, 1, 0],
            'instagram' => ['Instagram', 1, 1, 1],
            //  'hh'=>array('HH',1,1,0),
            //  'commerce'=>array('Commerce',1,1,1),
            'skype' => ['Skype', 1, 1, 0],
            'google' => ['Google', 1, 1, 0],
            'google_name' => ['Google имя', 1, 1, 0],
            'googleplus' => ['Google+', 1, 1, 0],
            'apple' => ['Apple', 1, 1, 1],
            'rzd' => ['РЖД', 1, 1, 0],
            'aeroflot' => ['Аэрофлот', 1, 1, 0],
            //  'uralair'=>array('Уральские авиалинии ',1,1,1),
            //  'papajohns'=>array('Папа Джонс',1,1,0),
            'avito' => ['Авито', 1, 1, 1],
            //  'rz'=>array('Реестр залогов',1,0,0),
            'reestrzalogov' => ['Реестр залогов', 1, 0, 0],
            'fssp' => ['ФССП', 1, 0, 0],
            'fssp_suspect' => ['ФССП розыск', 1, 0, 0],
            'gisgmp' => ['ГИС ГМП', 1, 0, 1],
            'bankrot' => ['Банкроты', 1, 0, 0],
            'terrorist' => ['Террористы', 1, 0, 0],
            'mvd' => ['МВД', 1, 0, 1],
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
