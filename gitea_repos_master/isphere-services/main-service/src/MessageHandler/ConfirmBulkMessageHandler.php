<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Contract\ScalarType;
use App\Controller\BulkController;
use App\Entity\Bulk;
use App\Entity\RequestNew;
use App\Message\AsyncProcessCommandMessage;
use App\Message\ConfirmBulkMessage;
use App\Model\EmailReq;
use App\Model\PersonReq;
use App\Model\PhoneReq;
use App\Model\Request;
use App\Repository\BulkRepository;
use Co\Channel;
use Co\WaitGroup;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumberUtil;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Swoole\Table;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

use function Co\defer;
use function Co\go;
use function Co\run;

#[AsMessageHandler]
class ConfirmBulkMessageHandler implements ContainerAwareInterface, LoggerAwareInterface
{
    use ContainerAwareTrait;
    use LoggerAwareTrait;

    private readonly PhoneNumberUtil $phoneNumberUtil;

    public function __construct(
        private readonly BulkRepository $bulkRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly SerializerInterface $serializer,
    ) {
        $this->phoneNumberUtil = PhoneNumberUtil::getInstance();
    }

    public function __invoke(ConfirmBulkMessage $message): void
    {
        $bulk = $this->bulkRepository->find($message->getBulkId());

        if (!$bulk) {
            $this->logger->error('Bulk not found by id', [
                'id' => $message->getBulkId(),
            ]);

            return;
        }

        if (Bulk::STATUS_WAIT_CONFIRMED !== $bulk->getStatus()) {
            $this->logger->error('Cannot process unconfirmed bulk', [
                'bulk_id' => $bulk->getIp(),
                'bulk_status' => $bulk->getStatus(),
            ]);

            return;
        }

        $bulk->setStatus(Bulk::STATUS_BEFORE_PROGRESS);

        $this->entityManager->persist($bulk);
        $this->entityManager->flush();

        run(function () use (&$bulk): void {
            $channel = new Channel();
            $wg = new WaitGroup();
            $rows = $bulk->getRows();
            $definitions = $bulk->getDefinitionsRepresentative();

            $table = new Table(\count($rows), 1.0);
            $table->column('serialized', Table::TYPE_STRING, 2 ** 10);
            $table->create();

            $this->logger->debug('Prepare task for scheduler', [
                'allocate_memory' => $table->getMemorySize().'b',
            ]);

            go(function () use (&$channel, &$table): void {
                while (true) {
                    if (false === ($data = $channel->pop())) {
                        break;
                    }

                    [$i, $request] = $data;
                    $serialized = $this->serializer->serialize($request, XmlEncoder::FORMAT, [
                        AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                        XmlEncoder::ENCODING => 'utf-8',
                        XmlEncoder::FORMAT_OUTPUT => true,
                        XmlEncoder::ROOT_NODE_NAME => 'Request',
                    ]);

                    $table->set((string) $i, ['serialized' => $serialized]);
                }
            });

            for ($i = 0, $m = \count($rows); $i < $m; ++$i) {
                $wg->add();

                go(function (int $i) use (&$bulk, &$rows, &$definitions, &$channel, &$wg): void {
                    defer(static fn () => $wg->done());

                    $row = $rows[$i];
                    $request = (new Request())
                        ->setRequestType(BulkController::NAME)
                        ->setUserId((string) $bulk->getUser()?->getId())
                        ->setPassword($bulk->getUser()?->getPassword())
                        ->setUserIp($bulk->getIp())
                        ->setRecursive(0)
                        ->setAsync(0);

                    foreach (\explode(',', $bulk->getSources()) as $source) {
                        $request->addSource($source);
                    }

                    $this->inject($bulk, $row, $request, $definitions);
                    $channel->push([$i, $request]);
                }, $i);
            }

            $wg->wait();
            $channel->close();

            $this->logger->debug('Building scheduling tasks');

            $bulk->setStatus(Bulk::STATUS_IN_PROGRESS);

            $this->entityManager->persist($bulk);
            $this->entityManager->flush();

            $wg = new WaitGroup();
            $channel = new Channel();

            $params = [
                '_clientId' => $bulk->getUser()?->getClient()?->getId(),
                '_connection' => $this->entityManager->getConnection(),
                '_reqdate' => \date('Y-m-d'),
                '_reqtime' => \date(\DateTimeInterface::ATOM),
                '_userId' => $bulk->getUser()?->getId(),
                '_xmlpath' => $this->container->getParameter('app.xml_path'),
                'recursive' => 0,
                'request_id' => null,
                'request_type' => BulkController::NAME,
                'user_ip' => $bulk->getIp(),
                'async' => 0,
            ];

            go(function () use (&$channel, &$bulk): void {
                while (true) {
                    if (false === ($next = $channel->pop())) {
                        break;
                    }

                    /* @var AsyncProcessCommandMessage $next */

                    $bulk->addRequest($this->entityManager->getPartialReference(RequestNew::class, $next->getReqId()));

                    $this->messageBus->dispatch($next);
                }
            });

            foreach ($table as $item) {
                $wg->add();

                go(function (array $item) use (&$bulk, &$params, &$channel, &$wg): void {
                    defer(static fn () => $wg->done());

                    $channel->push(
                        (new AsyncProcessCommandMessage(
                            $bulk->getUser()?->getId(),
                            $bulk->getIp(),
                            logRequest($params, $item['serialized']),
                            $item['serialized'],
                        ))
                            ->setBulkId($bulk->getId()),
                    );
                }, $item);
            }

            $wg->wait();
            $channel->close();
        });

        $this->entityManager->persist($bulk);
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function inject(Bulk $bulk, iterable $scalars, Request $request, array $definitions): void
    {
        foreach ($scalars as $i => $scalar) {
            if (!isset($definitions[$i])) {
                $this->logger->error('Broken definitions for bulk, no found column', [
                    'bulk_id' => $bulk->getId(),
                    'column_num' => $i,
                ]);

                continue;
            }

            if ($scalar->isEmpty()) {
                continue;
            }

            $definition = $definitions[$i];

            switch ($definition->getType()) {
                case ScalarType::BIRTHDAY:
                    $person = $request->getFirstPerson() ?? new PersonReq();
                    $person->setBirthday(new \DateTimeImmutable($scalar->getValue()));
                    $request->setFirstPerson($person);

                    break;

                case ScalarType::INN:
                    $person = $request->getFirstPerson() ?? new PersonReq();
                    $person->setInn($scalar->getValue());
                    $request->setFirstPerson($person);

                    break;

                case ScalarType::NAME:
                    $person = $request->getFirstPerson() ?? new PersonReq();
                    $person->setName($scalar->getValue());
                    $request->setFirstPerson($person);

                    break;

                case ScalarType::NAME_PATRONYMIC_SURNAME:
                    $person = $request->getFirstPerson() ?? new PersonReq();

                    if (\preg_match('~^(?P<name>\S+)\s+(?P<patronymic>.+)?\s+(?P<surname>\S+)$~', $scalar->getValue(), $m)) {
                        $person
                            ->setSurname($m['surname'])
                            ->setName($m['name'])
                            ->setPatronymic($m['patronymic']);
                    }

                    $request->setFirstPerson($person);

                    break;

                case ScalarType::PATRONYMIC:
                    $person = $request->getFirstPerson() ?? new PersonReq();
                    $person->setPatronymic($scalar->getValue());
                    $request->setFirstPerson($person);

                    break;

                case ScalarType::PHONE:
                    $phone = $request->getFirstPhone() ?? new PhoneReq();
                    $number = \preg_replace('~\D+~', '', $scalar->getValue());

                    if (\preg_match('~(\d{10})$~', $number, $m)
                        && (null !== ($phoneNumber = $this->phoneNumberUtil->parse($m[1], 'RU')))
                    ) {
                        $phone->setPhone('+'.$phoneNumber->getCountryCode().$phoneNumber->getNationalNumber());
                    }

                    $request->setFirstPhone($phone);

                    break;

                case ScalarType::RUSSIAN_PASSPORT:
                    $person = $request->getFirstPerson() ?? new PersonReq();
                    $value = \preg_replace('~\D+~', '', $scalar->getValue());

                    if (\preg_match('~^(?P<series>\d{4})(?P<number>\d+)$~', $value, $m)) {
                        $person
                            ->setPassportSeries($m['series'])
                            ->setPassportNumber($m['number']);
                    }

                    $request->setFirstPerson($person);

                    break;

                case ScalarType::RUSSIAN_PASSPORT_NUMBER:
                    $person = $request->getFirstPerson() ?? new PersonReq();
                    $value = \preg_replace('~\D+~', '', $scalar->getValue());

                    $person->setPassportNumber($value);
                    $request->setFirstPerson($person);

                    break;

                case ScalarType::RUSSIAN_PASSPORT_SERIES:
                    $person = $request->getFirstPerson() ?? new PersonReq();
                    $value = \preg_replace('~\D+~', '', $scalar->getValue());

                    $person->setPassportSeries($value);
                    $request->setFirstPerson($person);

                    break;

                case ScalarType::SURNAME:
                    $person = $request->getFirstPerson() ?? new PersonReq();
                    $person->setSurname($scalar->getValue());
                    $request->setFirstPerson($person);

                    break;

                case ScalarType::SURNAME_NAME_PATRONYMIC:
                    $person = $request->getFirstPerson() ?? new PersonReq();

                    if (\preg_match('~^(?P<surname>\S+)\s+(?P<name>\S+)\s+(?P<patronymic>.+)$~', $scalar->getValue(), $m)) {
                        $person
                            ->setSurname($m['surname'])
                            ->setName($m['name'])
                            ->setPatronymic($m['patronymic']);
                    }

                    $request->setFirstPerson($person);

                    break;

                case ScalarType::EMAIL:
                    $email = $request->getFirstEmail() ?? new EmailReq();
                    $email->setEmail($scalar->getValue());
                    $request->setFirstEmail($email);

                    break;

                default:
                    if (ScalarType::UNKNOWN !== $definition->getType()) {
                        $this->logger->warning('Skipping unknown scalar type', [
                            'bulk_id' => $bulk->getId(),
                            'scalar_type' => $definition->getType(),
                        ]);
                    }

                    break;
            }
        }
    }
}
