<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Form\Type\CheckOrgType;
use App\Model\CheckOrg;
use App\Model\OrgReq;
use App\Model\Request as AppRequest;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkorg', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_CHECK_ORG)]
class CheckOrgController extends AbstractFormController
{
    public const NAME = 'checkorg';

    protected function getFormClass(): string
    {
        return CheckOrgType::class;
    }

    protected function getName(): string
    {
        return self::NAME;
    }

    protected function getTemplateTitle(): string
    {
        return 'Проверка организации';
    }

    protected function appRequestFactory(mixed $check): AppRequest
    {
        \assert($check instanceof CheckOrg);

        return (new AppRequest())
            ->addOrg(
                (new OrgReq())
                    ->setInn($check->getInn())
                    ->setRegionId($check->getRegionId())
            );
    }
}
