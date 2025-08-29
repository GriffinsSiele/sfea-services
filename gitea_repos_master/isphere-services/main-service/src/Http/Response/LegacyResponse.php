<?php

declare(strict_types=1);

namespace App\Http\Response;

use App\Entity\SystemUser;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class LegacyResponse extends Response
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly ContainerInterface $container,
        private readonly EntityManagerInterface $entityManager,
        private readonly Kernel $kernel,
        private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly SystemUser $user,
        private readonly string $filename,
    ) {
        parent::__construct('', Response::HTTP_OK, []);
    }

    public function send(): static
    {
        $authorizationChecker = $this->authorizationChecker;
        $container = $this->container;
        $entityManager = $this->entityManager;
        $kernel = $this->kernel;
        $logger = $this->logger;
        $request = $this->request;
        $tokenStorage = $this->tokenStorage;
        $urlGenerator = $this->urlGenerator;
        $user = $this->user;

        $workpath = $container->getParameter('kernel.project_dir');
        $logpath = $container->getParameter('kernel.logs_dir').'/'.$container->getParameter('kernel.environment').'/protocol/';
        $xmlpath = $container->getParameter('kernel.logs_dir').'/'.$container->getParameter('kernel.environment').'/xml/';
        $serviceurl = ($_SERVER['HTTP_X_ORIGIN_URL'] ?? $request->getSchemeAndHttpHost()).'/';
        $http_agent = $container->getParameter('app.guzzle.user_agent');
        $http_connecttimeout = 5;
        $http_timeout = 55;
        $total_timeout = 180;
        $form_timeout = $container->getParameter('app.guzzle.timeout');
        $servicenames = [
//            'i-sphere.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
//            'www.i-sphere.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
//            'my.i-sphere.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
//            'infosfera.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
//            'www.infosfera.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
//            'lk.infosfera.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
            'my.infohub24.ru' => '<a href="http://infohub24.ru" target="_blank">Инфохаб</a>',
        ];

        // this is magic
        $response = require $this->filename;

        if ($response instanceof Response) {
            foreach ($this->headers->all() as $key => $values) {
                if (!$response->headers->has($key)) {
                    $response->headers->set($key, $values);
                }
            }

            $response->send();

            return $this;
        }

        if (\function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (\function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        } elseif (!\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {
            static::closeOutputBuffers(0, true);
            \flush();
        }

        return $this;
    }
}
