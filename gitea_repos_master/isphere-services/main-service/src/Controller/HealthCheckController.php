<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/healthcheck', methods: [Request::METHOD_GET])]
class HealthCheckController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        // database
        private readonly Connection $cbrConnection,
        private readonly Connection $commerceConnection,
        private readonly Connection $defaultConnection,
        private readonly Connection $fedsfmConnection,
        private readonly Connection $fnsConnection,
        private readonly Connection $rossvyazConnection,
        private readonly Connection $statsConnection,
        private readonly Connection $vkConnection,

        // transport
        private readonly TransportInterface $messengerTransportAsync,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $errors = [];

        $response = [
            'connection' => [
                'database' => [
                    'cbr' => false,
                    'commerce' => false,
                    'default' => false,
                    'fedsfm' => false,
                    'fns' => false,
                    'rossvyaz' => false,
                    'stats' => false,
                    'vk' => false,
                ],
                'transport' => [
                    'async' => false,
                ],
                'keydb' => [],
            ],
        ];

        // database
        foreach (
            [
                'cbr' => $this->cbrConnection,
                'commerce' => $this->commerceConnection,
                'default' => $this->defaultConnection,
                'fedsfm' => $this->fedsfmConnection,
                'fns' => $this->fnsConnection,
                'rossvyaz' => $this->rossvyazConnection,
                'stats' => $this->statsConnection,
                'vk' => $this->vkConnection,
            ] as $key => $connection
        ) {
            \assert($connection instanceof Connection);

            try {
                $response['connection']['database'][$key] = $this->isConnectionRespond($connection);
            } catch (\Throwable $e) {
                $this->logger->error($e->getMessage());

                $errors['connection']['database'][$key][] = $e->getMessage();
            }
        }

        // transport
        if ($this->messengerTransportAsync instanceof AmqpTransport) {
            try {
                $messageCount = $this->messengerTransportAsync->getMessageCount();
                $response['connection']['transport']['async'] = $messageCount >= 0;
            } catch (\Throwable $e) {
                $this->logger->error($e->getMessage());

                $errors['connection']['transport']['async'][] = $e->getMessage();
            }
        } else {
            $error = \sprintf(
                'Expected argument of type "%s", "%s" given',
                AmqpTransport::class,
                \get_class($this->messengerTransportAsync),
            );

            $this->logger->error($error);

            $errors['connection']['transport']['async'] = $error;
        }

        $response['connection']['keydb']['172.16.11.1'] = false;

        for ($i = 0; $i < 10; ++$i) {
            $ip = '172.16.1.25'.(3 + $i % 2);
            $response['connection']['keydb'][$ip] = false;
        }

        foreach ($response['connection']['keydb'] as $ip => $_) {
            try {
                if (false === ($fp = \fsockopen($ip, 6379, $errCode, $errStr, 1))) {
                    throw new \RuntimeException('Could not open socket: %s (%d)', $errStr, $errCode);
                }

                \fclose($fp);

                $response['connection']['keydb'][$ip] = true;
            } catch (\Throwable $e) {
                $errors['connection']['keydb'][$ip][] = $e->getMessage();
            }
        }

        if (\count($errors) > 0) {
            $response['errors'] = $errors;
        }

        return $this->json(
            $response,
            0 === \count($errors)
                ? Response::HTTP_OK
                : Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }

    private function isConnectionRespond(Connection $connection): bool
    {
        $stmt = $connection->prepare('select 1 + 1');
        $result = $stmt->executeQuery();

        return 2 === $result->fetchOne();
    }
}
