<?php

declare(strict_types=1);

namespace App\Messenger\Serializer;

use App\Message\CollectorRequestMessage;
use App\Model\Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\AckStamp;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;

class CollectorSerializer implements SerializerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly SymfonySerializerInterface $serializer,
    ) {
    }

    public function encode(Envelope $envelope): array
    {
        $message = $envelope->getMessage();

        \assert($message instanceof CollectorRequestMessage);

        $data = $message->getRequest();

        $allStamps = [];

        foreach ($envelope->all() as $stampKey => $stamps) {
            if (\in_array($stampKey, [AckStamp::class, ErrorDetailsStamp::class], true)) {
                continue;
            }

            $allStamps = \array_merge($allStamps, $stamps);
        }

        return [
            'headers' => [
                'stamps' => \serialize($allStamps),
            ],
            'body' => $this->serializer->serialize($data, XmlEncoder::FORMAT, [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                XmlEncoder::ROOT_NODE_NAME => 'Request',
            ]),
        ];
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        \assert(isset($encodedEnvelope['headers'], $encodedEnvelope['body']));

        $headers = $encodedEnvelope['headers'];
        $body = $encodedEnvelope['body'];

        $stamps = [];

        if (isset($headers['stamps'])) {
            $stamps = \unserialize($headers['stamps']);
        }

        $request = new Request();

        try {
            $request = $this->serializer->deserialize($body, Request::class, XmlEncoder::FORMAT);
        } catch (\Throwable $e) {
            $this->logger->error('Could not deserialize request', [
                'envelope' => $encodedEnvelope,
                'exception' => $e,
            ]);
        }

        return new Envelope(new CollectorRequestMessage($request), $stamps);
    }
}
