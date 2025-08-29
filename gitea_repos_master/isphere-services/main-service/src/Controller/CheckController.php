<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Form\Type\CheckType;
use App\Model\Check;
use App\Model\EmailReq;
use App\Model\PersonReq;
use App\Model\PhoneReq;
use App\Model\Request as AppRequest;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/check', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_CHECK)]
class CheckController extends AbstractFormController
{
    public const NAME = 'check';

    protected function getFormClass(): string
    {
        return CheckType::class;
    }

    protected function getName(): string
    {
        return self::NAME;
    }

    protected function getTemplateTitle(): string
    {
        return 'Проверка физ.лица';
    }

    protected function appRequestFactory(mixed $check): AppRequest
    {
        \assert($check instanceof Check);

        $appRequest = (new AppRequest())
            ->addPerson(
                (new PersonReq())
                    ->setSurname($check->getLastName())
                    ->setName($check->getFirstName())
                    ->setPatronymic($check->getPatronymic())
                    ->setBirthday($check->getDate())
                    ->setPassportSeries($check->getPassportSeries())
                    ->setPassportNumber($check->getPassportNumber())
                    ->setPassportIssueAt($check->getIssueDate())
                    ->setInn($check->getInn())
                    ->setSnils($check->getSnils())
                    ->setDriverLicenseNumber($check->getDriverNumber())
                    ->setDriverLicenseIssueAt($check->getDriverDate())
                    ->setRegionId($check->getRegionId())
            );

        if ($mobilePhone = $check->getMobilePhone()) {
            $appRequest->addPhone(
                (new PhoneReq())
                    ->setPhone($mobilePhone),
            );
        }

        if ($homePhone = $check->getHomePhone()) {
            $appRequest->addPhone(
                (new PhoneReq())
                    ->setPhone($homePhone),
            );
        }

        if ($workPhone = $check->getWorkPhone()) {
            $appRequest->addPhone(
                (new PhoneReq())
                    ->setPhone($workPhone),
            );
        }

        if ($additionalPhone = $check->getAdditionalPhone()) {
            $appRequest->addPhone(
                (new PhoneReq())
                    ->setPhone($additionalPhone),
            );
        }

        if ($email = $check->getEmail()) {
            $appRequest->addEmail(
                (new EmailReq())
                    ->setEmail($email),
            );
        }

        if ($additionalEmail = $check->getAdditionalEmail()) {
            $appRequest->addEmail(
                (new EmailReq())
                    ->setEmail($additionalEmail),
            );
        }

        return $appRequest;
    }
}
