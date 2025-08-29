<?php

declare(strict_types=1);

namespace App\Menu;

use App\Controller\BulkController;
use App\Controller\CheckAutoController;
use App\Controller\CheckCardController;
use App\Controller\CheckController;
use App\Controller\CheckEmailController;
use App\Controller\CheckIPController;
use App\Controller\CheckOrgController;
use App\Controller\CheckPhoneBGController;
use App\Controller\CheckPhoneController;
use App\Controller\CheckPhoneKZController;
use App\Controller\CheckPhonePLController;
use App\Controller\CheckPhonePTController;
use App\Controller\CheckPhoneROController;
use App\Controller\CheckPhoneUZController;
use App\Controller\CheckSkypeController;
use App\Controller\CheckUrlController;
use App\Controller\CheyTelefonController;
use App\Controller\HistoryController;
use App\Controller\NewsController;
use App\Controller\ReportsController;
use App\Controller\SourcesController;
use App\Entity\AccessRoles;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuBuilder
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly FactoryInterface $factory,
        private readonly Security $security,
    ) {
    }

    public function createMainMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => [
                'class' => 'list-unstyled',
            ],
        ]);

        $this->addCheckers($menu);

        return $menu;
    }

    public function createTopMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => [
                'id' => 'top-menu',
                'class' => 'mb-0 list-inline',
            ],
        ]);

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_BULK)) {
            $menu
                ->addChild('Обработка реестра', [
                    'route' => BulkController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none')
                ->setAttribute('class', 'list-inline-item');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_NEWS)
            || $this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_SOURCES)
        ) {
            if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_NEWS)) {
                $classes = ['link-body-emphasis', 'text-decoration-none'];

                if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_BULK)) {
                    $classes[] = 'divider-left';
                }

                $menu
                    ->addChild('Новости', [
                        'route' => NewsController::NAME,
                    ])
                    ->setLinkAttribute('class', \implode(' ', $classes))
                    ->setAttribute('class', 'list-inline-item');
            }

            if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_SOURCES)) {
                $classes = ['link-body-emphasis', 'text-decoration-none'];

                if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_BULK)
                    && !$this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_NEWS)
                ) {
                    $classes[] = 'divider-left';
                }

                $menu
                    ->addChild('Источники', [
                        'route' => SourcesController::NAME,
                    ])
                    ->setLinkAttribute('class', \implode(' ', $classes))
                    ->setAttribute('class', 'list-inline-item');
            }
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_HISTORY)
            || $this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_REPORTS)
        ) {
            if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_HISTORY)) {
                $classes = ['link-body-emphasis', 'text-decoration-none'];

                if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_BULK)
                    && ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_NEWS)
                        || $this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_SOURCES)
                    )
                ) {
                    $classes[] = 'divider-left';
                }

                $menu
                    ->addChild('История запросов', [
                        'route' => HistoryController::NAME,
                    ])
                    ->setLinkAttribute('class', \implode(' ', $classes))
                    ->setAttribute('class', 'list-inline-item');
            }

            if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_REPORTS)) {
                $classes = ['link-body-emphasis', 'text-decoration-none'];

                if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_BULK)
                    && ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_NEWS)
                        || $this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_SOURCES)
                    )
                    && !$this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_HISTORY)
                ) {
                    $classes[] = 'divider-left';
                }

                $menu
                    ->addChild('Статистика', [
                        'route' => ReportsController::NAME,
                    ])
                    ->setLinkAttribute('class', \implode(' ', $classes))
                    ->setAttribute('class', 'list-inline-item');
            }
        }

        $menu
            ->addChild('Переключить тему оформления', [
                'label' => '<i class="fa fa-regular fa-lightbulb"></i>',
                'uri' => '#',
            ])
            ->setAttribute('class', 'list-inline-item')
            ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none divider-left')
            ->setLinkAttribute('id', 'toggle-theme')
            ->setExtra('safe_label', true);

        $menu
            ->addChild($this->security->getUser()?->getUserIdentifier(), [
                'route' => 'app_logout',
//                'uri' => '/logout.php',
            ])
            ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none')
            ->setAttribute('class', 'list-inline-item');

        return $menu;
    }

    public function createCheckersMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root', [
            'childrenAttributes' => [
                'class' => 'list-unstyled',
            ],
        ]);

        $this->addCheckers($menu);

        return $menu;
    }

    private function addCheckers(ItemInterface $item): void
    {
        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK)) {
            $item
                ->addChild('Проверка физ.лица', [
                    'route' => CheckController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_ORG)) {
            $item
                ->addChild('Проверка организации', [
                    'route' => CheckOrgController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_PHONE)) {
            $item
                ->addChild('Проверка телефона 🇷🇺', [
                    'route' => CheckPhoneController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_PHONE_KZ)) {
            $item
                ->addChild('Проверка телефона 🇰🇿', [
                    'route' => CheckPhoneKZController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_PHONE_UZ)) {
            $item
                ->addChild('Проверка телефона 🇺🇿', [
                    'route' => CheckPhoneUZController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_PHONE_BG)) {
            $item
                ->addChild('Проверка телефона 🇧🇬', [
                    'route' => CheckPhoneBGController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_PHONE_RO)) {
            $item
                ->addChild('Проверка телефона 🇷🇴', [
                    'route' => CheckPhoneROController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_PHONE_PL)) {
            $item
                ->addChild('Проверка телефона 🇵🇱', [
                    'route' => CheckPhonePLController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_PHONE_PT)) {
            $item
                ->addChild('Проверка телефона 🇵🇹', [
                    'route' => CheckPhonePTController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_EMAIL)) {
            $item
                ->addChild('Проверка e-mail', [
                    'route' => CheckEmailController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_URL)) {
            $item
                ->addChild('Проверка профиля соцсети', [
                    'route' => CheckUrlController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_SKYPE)) {
            $item
                ->addChild('Проверка skype', [
                    'route' => CheckSkypeController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_AUTO)) {
            $item
                ->addChild('Проверка автомобиля', [
                    'route' => CheckAutoController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_IP)) {
            $item
                ->addChild('Проверка ip-адреса', [
                    'route' => CheckIPController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_CARD)) {
            $item
                ->addChild('Проверка карты', [
                    'route' => CheckCardController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }

        if ($this->authorizationChecker->isGranted(AccessRoles::ROLE_CHECK_CHEY)) {
            $item
                ->addChild('Чей телефон', [
                    'route' => CheyTelefonController::NAME,
                ])
                ->setLinkAttribute('class', 'link-body-emphasis text-decoration-none');
        }
    }

    private function addDivider(ItemInterface $item): void
    {
        $item
            ->addChild('divider'.\uniqid('divider', true), [
                'label' => '<hr/>',
            ])
            ->setExtra('safe_label', true);
    }
}
