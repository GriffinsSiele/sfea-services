<?php

declare(strict_types=1);

namespace App\Test;

use App\Model\Response;
use App\Test\Model\Params;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

trait TestTrait
{
    private ContainerInterface $container;
    private LoggerInterface $logger;
    private ParamsFactory $paramsFactory;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    private function init(): void
    {
        self::bootKernel();

        $kernel = static::$kernel;

        $GLOBALS['app'] = $kernel;

        $this->container = $kernel->getContainer();

        $this->logger = $this->container->get('app.logger');
        $this->paramsFactory = $this->container->get(ParamsFactory::class);
        $this->serializer = $this->container->get('app.serializer');
        $this->validator = $this->container->get('app.validator');
    }

    private function execute(Params $params, array $groups): void
    {
        $serialized = runRequests($params->toArray());
        $response = $this->serializer->deserialize($serialized, Response::class, XmlEncoder::FORMAT);

        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($response, groups: $groups);

        /** @var ConstraintViolation $error */
        foreach ($errors as $error) {
            $this->logger->error('Validation error', [
                'property_path' => $error->getPropertyPath(),
                'constraint' => $error->getMessage(),
                'actual_value' => $error->getInvalidValue(),
            ]);
        }

        self::assertSame(0, $errors->count());
    }
}
