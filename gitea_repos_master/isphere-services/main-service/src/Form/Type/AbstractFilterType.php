<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Client;
use App\Entity\ResponseNew;
use App\Entity\SystemUser;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractFilterType extends AbstractType
{
    private EntityManagerInterface $entityManager;

    private Security $security;

    #[Required]
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    #[Required]
    public function setSecurity(Security $security): void
    {
        $this->security = $security;
    }

    protected function addClientField(FormBuilderInterface $builder, array $options): void
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

        if ($user->getAccessArea() >= 3) {
            $builder->add('client', EntityType::class, [
                'label' => 'Клиент',
                'class' => Client::class,
                'choice_label' => 'code',
                'placeholder' => 'Все клиенты',
                'required' => false,
                ...$rowAttr,
                'query_builder' => static function (EntityRepository $repository) use ($user): QueryBuilder {
                    $qb = $repository->createQueryBuilder('c');
                    $qb
                        ->select('c')
                        ->orderBy('c.code', Criteria::ASC);

                    if ($user->getAccessArea() < 4) {
                        $qb
                            ->andWhere($qb->expr()->eq('c.id', ':clientId'))
                            ->setParameter('clientId', $user->getClient()?->getId());

                        if ($user->getAccessArea() >= 3) {
                            $qb
                                ->orWhere($qb->expr()->eq('c.masterUserId', ':userId'))
                                ->setParameter('userId', $user->getId());
                        }
                    }

                    return $qb;
                },
            ]);
        } else {
            $builder->add('client', HiddenType::class, [
                'data' => $user->getClient()?->getId(),
            ]);

            $builder->get('client')->addModelTransformer(
                new CallbackTransformer(fn ($id) => $id, function (?int $id): ?Client {
                    if (null === $id) {
                        return null;
                    }

                    return $this->entityManager->getReference(Client::class, $id);
                })
            );
        }
    }

    protected function addUserField(FormBuilderInterface $builder, array $options): void
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

        $builder->add('user', EntityType::class, [
            'label' => 'Пользователь',
            'class' => SystemUser::class,
            ...$rowAttr,
            'choice_label' => static function (?SystemUser $choice) use ($user): ?string {
                if (null === $choice) {
                    return null;
                }

                $components = [
                    $choice->getLogin(),
                ];

                if ($choice->getLocked()) {
                    $components[] = '(-)';
                }

                if ($user->getId() === $choice->getId()) {
                    $components[] = '(я)';
                }

                return \implode(' ', $components);
            },
            'placeholder' => 'Все пользователи',
            'required' => false,
            'query_builder' => static function (EntityRepository $repository) use ($user): QueryBuilder {
                $qb = $repository->createQueryBuilder('u');
                $qb
                    ->select('u')
                    ->orderBy('u.login', Criteria::ASC);

                if ($user->getAccessArea() < 4) {
                    $qb
                        ->andWhere($qb->expr()->eq('u.id', ':userId'))
                        ->setParameter('userId', $user->getId());

                    if ($user->getAccessArea() <= 1) {
                        $qb->orWhere($qb->expr()->eq('u.masterUserId', ':userId'));
                    }

                    if ($user->getAccessArea() >= 2) {
                        $qb
                            ->orWhere($qb->expr()->eq('u.clientId', ':clientId'))
                            ->setParameter('clientId', $user->getClient()?->getId());
                    }

                    if ($user->getAccessArea() >= 3) {
                        $qb2 = $repository->createQueryBuilder('u2');
                        $qb2->where($qb2->expr()->eq('u2.masterUserId', ':userId'));
                        $qb->orWhere($qb->expr()->in('u.clientId', $qb2->getQuery()->getDQL()));
                    }
                }

                return $qb;
            },
        ]);
    }

    protected function addSourceField(FormBuilderInterface $builder, array $options): void
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

        if ($user->getAccessLevel() < 0) {
            $builder->add('source', ChoiceType::class, [
                'label' => 'Источник',
                'required' => false,
                'placeholder' => 'Все источники',
                ...$rowAttr,
                'choice_loader' => new CallbackChoiceLoader(function () {
                    $qb = $this->entityManager->createQueryBuilder();
                    $qb
                        ->select('r')
                        ->distinct()
                        ->from(ResponseNew::class, 'r')
                        ->orderBy('r.sourceName', Criteria::ASC);

                    $choices = [];

                    foreach ($qb->getQuery()->toIterable() as $equipment) {
                        \assert($equipment instanceof ResponseNew);

                        $checkType = $equipment->getSourceName();

                        $choices[$checkType] = $checkType;
                    }

                    return $choices;
                }),
            ]);
        }
    }

    protected function addCheckTypeField(FormBuilderInterface $builder, array $options): void
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

        if ($user->getAccessLevel() < 0) {
            $builder->add('checkType', ChoiceType::class, [
                'label' => 'Проверка',
                'required' => false,
                'placeholder' => 'Все проверки',
                ...$rowAttr,
                'choice_loader' => new CallbackChoiceLoader(function () {
                    $qb = $this->entityManager->createQueryBuilder();
                    $qb
                        ->select('r')
                        ->distinct()
                        ->from(ResponseNew::class, 'r')
                        ->orderBy('r.checkType', Criteria::ASC);

                    $choices = [];

                    foreach ($qb->getQuery()->toIterable() as $equipment) {
                        \assert($equipment instanceof ResponseNew);

                        $checkType = $equipment->getCheckType();

                        $choices[$checkType] = $checkType;
                    }

                    return $choices;
                }),
            ]);
        }
    }
}
