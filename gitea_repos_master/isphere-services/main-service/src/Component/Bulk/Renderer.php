<?php

declare(strict_types=1);

namespace App\Component\Bulk;

use App\Component\Bulk\Model\RenderingData;
use App\Contract\ScalarType;
use App\Entity\Bulk;
use App\Model\Request;
use App\Model\Response;
use App\Twig\RequestNewExtension;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Renderer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly RequestNewExtension $requestNewExtension,
        private readonly SerializerInterface $serializer,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function render(Bulk $bulk): RenderingData
    {
        $sourceHeaders = [];
        $hasAtLeastOneIdentifiedDefinition = $bulk->hasAtLeastOneIdentifiedDefinition();

        foreach ($bulk->getDefinitionsRepresentative() as $definition) {
            if (!$hasAtLeastOneIdentifiedDefinition || $definition->isIdentifier()) {
                $definitionType = $definition->getType();
                $sourceHeaders[$definitionType->value] = $this->translator->trans($definitionType->name, [], 'scalar_types');
            }
        }

        if (!isset($sourceHeaders['ResultCode'])) {
            $sourceHeaders['ResultCode'] = 'Код результата';
        }

        $headers = [];
        $rows = [];

        foreach ($bulk->getRequests() as $requestNew) {
            $titles = $this->requestNewExtension->makeTitles($requestNew);
            $serializedRequest = $this->requestNewExtension->downloadFile($titles, 'req');

            if (null === $serializedRequest) {
                $this->logger->warning('Empty RequestNew detected', [
                    'bulk_id' => $bulk->getId(),
                    'request_new_id' => $requestNew->getId(),
                ]);

                continue;
            }

            /** @var Request $request */
            $request = $this->serializer->deserialize($serializedRequest, Request::class, XmlEncoder::FORMAT);
            $in = [];

            foreach ($sourceHeaders as $header => $_) {
                $in[$header] = match ($header) {
                    ScalarType::BIRTHDAY->value => $request->getFirstPerson()?->getBirthday()?->format('Y-m-d'),
                    ScalarType::INN->value => $request->getFirstPerson()?->getInn(),
                    ScalarType::NAME->value => $request->getFirstPerson()?->getName(),
                    ScalarType::NAME_PATRONYMIC_SURNAME->value => (static fn (): string => \implode(' ', [
                        $request->getFirstPerson()?->getName(),
                        $request->getFirstPerson()?->getPatronymic(),
                        $request->getFirstPerson()?->getSurname(),
                    ]))(),
                    ScalarType::PATRONYMIC->value => $request->getFirstPerson()?->getPatronymic(),
                    ScalarType::PHONE->value => $request->getFirstPhone()?->getPhone(),
                    ScalarType::RUSSIAN_PASSPORT->value => (static fn (): string => \implode(' ', [
                        $request->getFirstPerson()?->getPassportSeries(),
                        $request->getFirstPerson()?->getPassportNumber(),
                    ]))(),
                    ScalarType::RUSSIAN_PASSPORT_NUMBER->value => $request->getFirstPerson()?->getPassportNumber(),
                    ScalarType::RUSSIAN_PASSPORT_SERIES->value => $request->getFirstPerson()?->getPassportSeries(),
                    ScalarType::SURNAME->value => $request->getFirstPerson()?->getSurname(),
                    ScalarType::SURNAME_NAME_PATRONYMIC->value => (static fn (): string => \implode(' ', [
                        $request->getFirstPerson()?->getSurname(),
                        $request->getFirstPerson()?->getName(),
                        $request->getFirstPerson()?->getPatronymic(),
                    ]))(),
                    ScalarType::EMAIL->value => $request->getFirstEmail()?->getEmail(),
                    'ResultCode' => null,
                    default => throw new \RuntimeException('Unknown scalar type: '.$header),
                };
            }

            $serializedResponse = $this->requestNewExtension->downloadFile($titles, 'res');

            /** @var Response $response */
            $response = $this->serializer->deserialize($serializedResponse ?? '<Response></Response>', Response::class, XmlEncoder::FORMAT);

            if ($response->getSourcesCount() > 0) {
                foreach ($response->getSources() as $source) {
                    if (!isset($headers[$source->getCode()])) {
                        $headers[$source->getCode()] = [...$sourceHeaders];
                    }

                    if (!isset($rows[$source->getCode()])) {
                        $rows[$source->getCode()] = [];
                    }

                    if ($source->getResultsCount() > 0) {
                        foreach ($source->getRecords() as $record) {
                            $out = [...$in];

                            foreach ($record->getFields() ?? [] as $field) {
                                if (!isset($headers[$source->getCode()][$field->getName()])) {
                                    $headers[$source->getCode()][$field->getName()] = $field->getTitle();
                                }

                                $out[$field->getName()] = $field->getValue();
                            }

                            if (null === $out['ResultCode']) {
                                $out['ResultCode'] = 'FOUND';
                            }

                            $rows[$source->getCode()][] = $out;
                        }
                    } else {
                        $rows[$source->getCode()][] = [
                            ...$in,
                            ...[
                                'ResultCode' => 'NOT FOUND',
                            ],
                        ];
                    }
                }
            } else {
                $this->logger->warning('Empty response for request', [
                    'bulk_id' => $bulk->getId(),
                    'request_new_id' => $requestNew->getId(),
                ]);
            }
        }

        foreach ($headers as $sourceCode => $sourceHeaders) {
            foreach ($rows[$sourceCode] as &$row) {
                foreach ($sourceHeaders as $k => $v) {
                    $row[$k] ??= null;
                }
            }

            unset($row);
        }

        return new RenderingData($headers, $rows);
    }
}
