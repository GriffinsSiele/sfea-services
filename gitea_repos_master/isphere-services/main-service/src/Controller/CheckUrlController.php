<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Form\Type\CheckURLType;
use App\Model\CheckURL;
use App\Model\Request as AppRequest;
use App\Model\URLReq;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkurl', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_CHECK_URL)]
class CheckUrlController extends AbstractFormController
{
    public const NAME = 'checkurl';

    protected function getFormClass(): string
    {
        return CheckURLType::class;
    }

    protected function getName(): string
    {
        return self::NAME;
    }

    protected function getTemplateTitle(): string
    {
        return 'Проверка профиля';
    }

    protected function appRequestFactory(mixed $check): AppRequest
    {
        \assert($check instanceof CheckURL);

        return (new AppRequest())
            ->addUrl(
                (new URLReq())
                    ->setUrl($check->getUrl()),
            );
    }
}
