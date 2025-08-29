<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Model\Period;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterPeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $rowAttr = [
            'row_attr' => [
                'class' => 'form-floating mb-3',
            ],
        ];

        $builder
            ->add('from', DateType::class, [
                'label' => 'Начало',
                'required' => false,
                'widget' => 'single_text',
                ...$rowAttr,
            ])
            ->add('to', DateType::class, [
                'label' => 'Конец',
                'required' => false,
                'widget' => 'single_text',
                ...$rowAttr,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Period::class,
        ]);
    }
}
