<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Form\Type\CheckSkypeType;
use App\Model\CheckSkype;
use App\Model\Request as AppRequest;
use App\Model\SkypeReq;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkskype', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_CHECK_EMAIL)]
class CheckSkypeController extends AbstractFormController
{
    public const NAME = 'checkskype';

    protected function getFormClass(): string
    {
        return CheckSkypeType::class;
    }

    protected function getName(): string
    {
        return self::NAME;
    }

    protected function getTemplateTitle(): string
    {
        return 'Проверка skype';
    }

    protected function appRequestFactory(mixed $check): AppRequest
    {
        \assert($check instanceof CheckSkype);

        return (new AppRequest())
            ->addSkype(
                (new SkypeReq())
                    ->setSkype($check->getSkype())
            );
    }
}
