<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\SystemUser;
use App\Model\StatsFilter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatsFilterType extends AbstractFilterType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $rowAttr = [
            'row_attr' => [
                'class' => 'form-floating mb-3',
            ],
        ];

        $user = $this->security->getUser();

        $this->addClientField($builder, $options);
        $this->addUserField($builder, $options);

        if ($user instanceof SystemUser && $user->getAccessArea() >= 2) {
            $builder->add('nested', CheckboxType::class, [
                'label' => '+дочерние',
                'required' => false,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
            ]);
        }

        $builder->add('period', FilterPeriodType::class, [
            'label' => 'Период',
        ]);

        if ($user instanceof SystemUser && $user->getAccessLevel() < 0) {
            $builder->add('ip', TextType::class, [
                'label' => 'IP',
                'required' => false,
                'attr' => [
                    'placeholder' => 'IP',
                ],
                ...$rowAttr,
            ]);
        }

        $this->addSourceField($builder, $options);
        $this->addCheckTypeField($builder, $options);
        $this->addTypeField($builder, $options);

        if ($user instanceof SystemUser && $user->getAccessArea() >= 3) {
            $builder->add('pay', ChoiceType::class, [
                'label' => 'Оплата',
                'choices' => [
                    'Не тарифицировать' => 'all',
                    'Все тарифы' => 'separate',
                    'Платные' => 'pay',
                    'Бесплатные' => 'free',
                    'Тестовые' => 'test',
                ],
                'data' => 'all',
                ...$rowAttr,
            ]);
        }

        $this->addOrderField($builder, $options);

        $builder->add('page', HiddenType::class, [
            'data' => 0,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StatsFilter::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    private function addTypeField(FormBuilderInterface $builder, array $options): void
    {
        $rowAttr = [
            'row_attr' => [
                'class' => 'form-floating mb-3',
            ],
        ];

        $user = $this->security->getUser();

        $builder->add('type', ChoiceType::class, [
            'label' => 'Группировка',
            'data' => 'sources',
            ...$rowAttr,
            'choice_loader' => new CallbackChoiceLoader(static function () use ($user) {
                $choices = [
                    'По датам' => 'dates',
                    'По месяцам' => 'months',
                    'По часам' => 'hours',
                    'По минутам' => 'minutes',
                    'По источникам' => 'sources',
                    'По проверкам' => 'checktypes',
                ];

                if ($user instanceof SystemUser) {
                    if ($user->getAccessArea() >= 1) {
                        $choices['По пользователям'] = 'users';
                    }

                    if ($user->getAccessArea() > 2) {
                        $choices['По клиентам'] = 'clients';
                    }
                }

                $choices['По IP-адресам'] = 'ips';

                return $choices;
            }),
        ]);
    }

    private function addOrderField(FormBuilderInterface $builder, array $options): void
    {
        $rowAttr = [
            'row_attr' => [
                'class' => 'form-floating mb-3',
            ],
        ];

        $user = $this->security->getUser();

        $builder->add('order', ChoiceType::class, [
            'label' => 'Сортировка',
            ...$rowAttr,
            'choice_loader' => new CallbackChoiceLoader(static function () use ($user) {
                $choices = [
                    'По умолчанию' => '1',
                ];

                if ($user instanceof SystemUser && $user->getAccessArea() >= 3) {
                    $choices['По убыванию суммы'] = 'total desc';
                }

                $choices['По убыванию обращений'] = 'reqcount desc';
                $choices['По убыванию запросов'] = 'rescount desc';

                return $choices;
            }),
            'data' => '1',
        ]);
    }
}
