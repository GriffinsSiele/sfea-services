<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Controller\DefaultController;
use App\Kernel;
use App\Message\CollectorRequestMessage;
use App\Repository\SystemUserRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[AsMessageHandler]
class CollectorRequestMessageHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly DefaultController $defaultController,
        private readonly Kernel $kernel,
        private readonly MessageBusInterface $messageBus,
        private readonly SerializerInterface $serializer,
        private readonly SystemUserRepository $systemUserRepository,
    ) {
    }

    public function __invoke(CollectorRequestMessage $message): void
    {
        $request = $message->getRequest();

        if (empty($request->getUserId())) {
            $this->logger->error('Could not handle request without userId', [
                'request' => $request,
            ]);

            return;
        }

        $user = $this->systemUserRepository->findOneByLogin($request->getUserId());

        if (null === $user) {
            $this->logger->error('User not found', [
                'request' => $request,
            ]);

            return;
        }

        $serialized = $this->serializer->serialize($request, XmlEncoder::FORMAT, [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            XmlEncoder::ROOT_NODE_NAME => 'Request',
        ]);

        $request = Request::create('/', Request::METHOD_POST, server: [
            'PHP_AUTH_USER' => $user->getUserIdentifier(),
            'PHP_AUTH_PW' => $user->getPassword(),
        ], content: $serialized);

        $response = $this->kernel->handle($request, HttpKernelInterface::MASTER_REQUEST);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $this->logger->error('Failed collector request', [
                'request' => $request,
                'response' => $response->getContent(),
            ]);
        }
    }
}
