<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Form\Type\CheckEmailType;
use App\Model\CheckEmail;
use App\Model\EmailReq;
use App\Model\Request as AppRequest;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkemail', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_CHECK_EMAIL)]
class CheckEmailController extends AbstractFormController
{
    public const NAME = 'checkemail';

    protected function getFormClass(): string
    {
        return CheckEmailType::class;
    }

    protected function getName(): string
    {
        return self::NAME;
    }

    protected function getTemplateTitle(): string
    {
        return 'Проверка e-mail';
    }

    protected function appRequestFactory(mixed $check): AppRequest
    {
        \assert($check instanceof CheckEmail);

        return (new AppRequest())
            ->addEmail(
                (new EmailReq())
                    ->setEmail($check->getEmail())
            );
    }
}
