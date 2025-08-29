<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Form\Type\CheckIPType;
use App\Model\CheckIP;
use App\Model\IPReq;
use App\Model\Request as AppRequest;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkip', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_CHECK_IP)]
class CheckIPController extends AbstractFormController
{
    public const NAME = 'checkip';

    protected function getFormClass(): string
    {
        return CheckIPType::class;
    }

    protected function getName(): string
    {
        return self::NAME;
    }

    protected function getTemplateTitle(): string
    {
        return 'Проверка ip-адреса';
    }

    protected function appRequestFactory(mixed $check): AppRequest
    {
        \assert($check instanceof CheckIP);

        return (new AppRequest())
            ->addIp(
                (new IPReq())
                    ->setIp($check->getIp())
            );
    }
}
