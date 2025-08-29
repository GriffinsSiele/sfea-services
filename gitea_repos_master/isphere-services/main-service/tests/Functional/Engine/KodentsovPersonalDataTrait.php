<?php

declare(strict_types=1);

namespace App\Tests\Functional\Engine;

use App\Model\CarReq;
use App\Model\PersonReq;
use App\Model\PhoneReq;
use App\Model\Request;

trait KodentsovPersonalDataTrait
{
    //    abstract protected function getConfiguration(): iterable;
    //
    //    protected function dataProvider(): array
    //    {
    //        $result = [];
    //
    //        foreach ($this->getConfiguration() as $params) {
    //            [$requestType, $source, $closure] = $params;
    //
    //            \assert(\is_string($requestType));
    //            \assert(\is_string($source));
    //            \assert(\is_callable($closure));
    //
    //            $result[] = [
    //                (new Request())
    //                    ->setRequestType($requestType)
    //                    ->setTimeout(30)
    //                    ->setRecursive(0)
    //                    ->setAsync(0)
    //                    ->addSource($source)
    //                    ->addCar(
    //                        (new CarReq())
    //                            ->setVin('XUUNA486JC0030559')
    //                            ->setBodyNumber('XUUNA486JC0030559')
    //                            ->setNumber('Е648АВ50')
    //                            ->setRegistrationCertificateNumber('5009882227')
    //                            ->setPassportNumber('30НО566144')
    //                            ->setRequestAt(new \DateTimeImmutable('01.05.2022'))
    //                    )
    //                    ->addPerson(
    //                        (new PersonReq())
    //                            ->setSurname('Коденцов')
    //                            ->setName('Сергей')
    //                            ->setPatronymic('Александрович')
    //                            ->setBirthday(new \DateTimeImmutable('23.02.1989'))
    //                            ->setDriverLicenseNumber('8221120839')
    //                            ->setDriverLicenseIssueAt(new \DateTimeImmutable('31.03.2023')),
    //                    )
    //                    ->addPhone(
    //                        (new PhoneReq())
    //                            ->setPhone('79772776278'),
    //                    ),
    //                $closure,
    //            ];
    //        }
    //
    //        return $result;
    //    }
}
