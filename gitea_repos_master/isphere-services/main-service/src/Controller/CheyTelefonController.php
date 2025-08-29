<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Form\Type\CheyTelefonType;
use App\Model\CheyTelefon;
use App\Model\PhoneReq;
use App\Model\Request as AppRequest;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cheytelefon', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_CHECK_PHONE)]
class CheyTelefonController extends AbstractFormController
{
    public const NAME = 'cheytelefon';

    protected function getFormClass(): string
    {
        return CheyTelefonType::class;
    }

    protected function getName(): string
    {
        return self::NAME;
    }

    protected function getTemplateTitle(): string
    {
        return 'Чей телефон';
    }

    protected function appRequestFactory(mixed $check): AppRequest
    {
        \assert($check instanceof CheyTelefon);

        return (new AppRequest())
            ->addPhone(
                (new PhoneReq())
                    ->setPhone($check->getMobilePhone())
            );
    }
}
