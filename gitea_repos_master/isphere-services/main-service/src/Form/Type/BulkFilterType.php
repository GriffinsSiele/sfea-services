<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\SystemUser;
use App\Model\BulkFilter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BulkFilterType extends AbstractFilterType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();

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
            $this->addSourceField($builder, $options);
        }

        $builder
            ->add('page', HiddenType::class)
            ->add('limit', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BulkFilter::class,
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
