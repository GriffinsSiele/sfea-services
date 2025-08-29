<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Controller\DefaultController;
use App\Entity\Bulk;
use App\Entity\SystemUser;
use App\Kernel;
use App\Message\AsyncProcessCommandMessage;
use App\Message\GenerateBulkFileMessage;
use App\Repository\BulkRepository;
use App\Repository\SystemUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[AsMessageHandler]
class AsyncProcessCommandMessageHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly BulkRepository $bulkRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Kernel $kernel,
        private readonly MessageBusInterface $messageBus,
        private readonly SystemUserRepository $systemUserRepository,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(AsyncProcessCommandMessage $message): void
    {
        /** @var SystemUser|null $user */
        $user = $this->systemUserRepository->find($message->getUserId());

        if (!$user) {
            $this->logger->error('Message is unprocessable, user not found', [
                'user_id' => $message->getUserId(),
            ]);

            return;
        }

        if (null !== $message->getBulkId()) {
            $bulkStatus = $this->bulkRepository->findStatusById($message->getBulkId());

            if (Bulk::STATUS_IN_PROGRESS !== $bulkStatus) {
                if (Bulk::STATUS_BEFORE_PROGRESS === $bulkStatus) {
                    $envelope = (new Envelope($message))
                        ->with(new DelayStamp(5_000)); // seconds

                    $this->messageBus->dispatch($envelope);

                    return;
                }

                $this->logger->debug('Skip the bulk on non-progress status', [
                    'bulk_id' => $message->getBulkId(),
                    'status' => $bulkStatus,
                ]);

                return;
            }
        }

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        $this->tokenStorage->setToken($token);

        $xml = $message->getXml();

        $_SERVER['HTTP_X_FORWARDED_FOR'] = $message->getClientIp();

        $request = Request::create(
            $this->urlGenerator->generate(DefaultController::NAME),
            Request::METHOD_POST,
            server: [
                'PHP_AUTH_PW' => $user->getPassword(),
                'PHP_AUTH_USER' => $user->getUserIdentifier(),
                'REQUEST_METHOD' => Request::METHOD_POST,
            ],
            content: $xml,
        );

        $request->attributes->set('_controller', DefaultController::class);
        $request->attributes->set('_reqId', $message->getReqId());
        $request->attributes->set('_skipMessengerDispatch', true);
        $request->attributes->set('_skipLimits', true);

        $response = $this->kernel->handle($request);

        if ((null !== $message->getBulkId())
            && $this->bulkRepository->incrementCountersById(
                $message->getBulkId(),
                Response::HTTP_OK === $response->getStatusCode()
            )
        ) {
            $this->bulkRepository->updateStatusById($message->getBulkId(), Bulk::STATUS_COMPLETED);

            $next = (new GenerateBulkFileMessage())
                ->setBulkId($message->getBulkId());

            $this->messageBus->dispatch(
                (new Envelope($next))
                    ->with(new DelayStamp(1_000)), // 10 seconds
            );
        }

        $this->entityManager->clear();
    }
}
