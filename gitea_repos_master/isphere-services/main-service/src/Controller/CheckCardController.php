<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Form\Type\CheckCardType;
use App\Model\CardReq;
use App\Model\CheckCard;
use App\Model\Request as AppRequest;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkcard', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_CHECK_CARD)]
class CheckCardController extends AbstractFormController
{
    public const NAME = 'checkcard';

    protected function getFormClass(): string
    {
        return CheckCardType::class;
    }

    protected function getName(): string
    {
        return self::NAME;
    }

    protected function getTemplateTitle(): string
    {
        return 'Проверка карты';
    }

    protected function appRequestFactory(mixed $check): AppRequest
    {
        \assert($check instanceof CheckCard);

        return (new AppRequest())
            ->addCard(
                (new CardReq())
                    ->setCard($check->getCardNumber())
            );
    }
}
