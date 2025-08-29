<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccessRoles;
use App\Form\Type\CheckCarType;
use App\Model\CarReq;
use App\Model\CheckCar;
use App\Model\OtherReq;
use App\Model\PersonReq;
use App\Model\Request as AppRequest;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/checkauto', name: self::NAME)]
#[IsGranted(AccessRoles::ROLE_CHECK_AUTO)]
class CheckAutoController extends AbstractFormController
{
    public const NAME = 'checkauto';

    protected function getFormClass(): string
    {
        return CheckCarType::class;
    }

    protected function getName(): string
    {
        return self::NAME;
    }

    protected function getTemplateTitle(): string
    {
        return 'Проверка автомобиля';
    }

    protected function appRequestFactory(mixed $check): AppRequest
    {
        \assert($check instanceof CheckCar);

        return (new AppRequest())
            ->addPerson(
                (new PersonReq())
                    ->setDriverLicenseNumber($check->getDriverNumber())
                    ->setDriverLicenseIssueAt($check->getDriverDate())
            )
            ->addCar(
                (new CarReq())
                    ->setVin($check->getVin())
                    ->setBodyNumber($check->getBodyNumber())
                    ->setChassis($check->getChassis())
                    ->setNumber($check->getRegNumber())
                    ->setRegistrationCertificateNumber($check->getCtc())
                    ->setPassportNumber($check->getPts())
                    ->setRequestAt($check->getReqDate())
            )
            ->addOther(
                (new OtherReq())
                    ->setOsago($check->getOsago()),
            );
    }
}
