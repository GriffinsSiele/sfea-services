<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\SystemUser;
use App\Model\HistoryFilter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HistoryFilterType extends AbstractFilterType
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

        $this->addSourceField($builder, $options);
        $this->addCheckTypeField($builder, $options);

        if ($user instanceof SystemUser && $user->getAccessLevel() < 0) {
            $builder->add('statusCode', ChoiceType::class, [
                'label' => 'Результат',
                'placeholder' => 'Все результаты',
                'required' => false,
                'choices' => [
                    'Найден' => 200,
                    'Не найден' => 204,
                    'Ошибка' => 500,
                ],
                ...$rowAttr,
            ]);
        }

        $builder->add('page', HiddenType::class, [
            'data' => 0,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HistoryFilter::class,
        ]);
    }
}
