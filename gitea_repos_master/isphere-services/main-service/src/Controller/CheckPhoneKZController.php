<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Form\Type\CheckPhoneKZType;
use App\Model\CheckPhoneKZ;
use App\Model\PhoneReq;
use App\Model\Request as AppRequest;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkphone_kz', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_CHECK_PHONE_KZ)]
class CheckPhoneKZController extends AbstractFormController
{
    public const NAME = 'checkphone_kz';

    protected function getFormClass(): string
    {
        return CheckPhoneKZType::class;
    }

    protected function getName(): string
    {
        return self::NAME;
    }

    protected function getTemplateTitle(): string
    {
        return 'Проверка телефона 🇰🇿';
    }

    protected function appRequestFactory(mixed $check): AppRequest
    {
        \assert($check instanceof CheckPhoneKZ);

        return (new AppRequest())
            ->addPhone(
                (new PhoneReq())
                    ->setPhone($check->getMobilePhone())
            );
    }
}
