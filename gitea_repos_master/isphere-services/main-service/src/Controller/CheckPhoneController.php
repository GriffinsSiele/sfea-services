<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Form\Type\CheckPhoneType;
use App\Model\CheckPhone;
use App\Model\PhoneReq;
use App\Model\Request as AppRequest;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkphone', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_CHECK_PHONE)]
class CheckPhoneController extends AbstractFormController
{
    public const NAME = 'checkphone';

    protected function getFormClass(): string
    {
        return CheckPhoneType::class;
    }

    protected function getName(): string
    {
        return self::NAME;
    }

    protected function getTemplateTitle(): string
    {
        return 'Проверка телефона 🇷🇺';
    }

    protected function appRequestFactory(mixed $check): AppRequest
    {
        \assert($check instanceof CheckPhone);

        return (new AppRequest())
            ->addPhone(
                (new PhoneReq())
                    ->setPhone($check->getMobilePhone())
            );
    }
}
