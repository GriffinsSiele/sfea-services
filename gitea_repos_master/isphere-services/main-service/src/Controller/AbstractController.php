<?php

declare(strict_types=1);

namespace App\Controller;

use App\Kernel;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Templating\PhpEngine;
use Symfony\Contracts\Service\Attribute\Required;

class AbstractController extends BaseAbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ?AuthorizationCheckerInterface $authorizationChecker = null;
    private ?Connection $connection = null;
    private ?EntityManagerInterface $entityManager = null;
    private ?Kernel $kernel = null;
    private ?PhpEngine $phpEngine = null;
    private ?RequestStack $requestStack = null;
    private ?TokenStorageInterface $tokenStorage = null;
    private ?UrlGeneratorInterface $urlGenerator = null;

    #[Required]
    public function setAuthorizationChecker(?AuthorizationCheckerInterface $authorizationChecker): void
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    #[Required]
    public function setConnection(?Connection $connection): void
    {
        $this->connection = $connection;
    }

    #[Required]
    public function setEntityManager(?EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    #[Required]
    public function setKernel(?Kernel $kernel): void
    {
        $this->kernel = $kernel;
    }

    #[Required]
    public function setRequestStack(?RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }

    #[Required]
    public function setPhpEngine(?PhpEngine $phpEngine): void
    {
        $this->phpEngine = $phpEngine;
    }

    #[Required]
    public function setTokenStorage(?TokenStorageInterface $tokenStorage): void
    {
        $this->tokenStorage = $tokenStorage;
    }

    #[Required]
    public function setUrlGenerator(?UrlGeneratorInterface $urlGenerator): void
    {
        $this->urlGenerator = $urlGenerator;
    }

    protected function renderView(string $view, array $parameters = []): string
    {
        if (!\str_ends_with($view, '.php')) {
            return parent::renderView($view, $parameters);
        }

        if (null === $this->phpEngine) {
            throw new \LogicException('You cannot use "renderView" method if the Templating Bundle is not available. Try running "composer require symfony/templating".');
        }

        $container = $this->kernel?->getContainer();
        $request = $this->requestStack?->getCurrentRequest();

        $parameters['authorizationChecker'] ??= $this->authorizationChecker;
        $parameters['connection'] ??= $this->connection;
        $parameters['container'] ??= $container;
        $parameters['entityManager'] ??= $this->entityManager;
        $parameters['kernel'] ??= $this->kernel;
        $parameters['logger'] ??= $this->logger;
        $parameters['request'] ??= $request;
        $parameters['urlGenerator'] ??= $this->urlGenerator;
        $parameters['user'] ??= $this->tokenStorage?->getToken()?->getUser();

        $parameters['form_timeout'] = $container->getParameter('app.guzzle.timeout');
        $parameters['http_agent'] = $container->getParameter('app.guzzle.user_agent');
        $parameters['http_connecttimeout'] = 5;
        $parameters['http_timeout'] = 55;
        $parameters['logpath'] = $container->getParameter('kernel.logs_dir').'/'.$container->getParameter('kernel.environment').'/protocol/';
        $parameters['serviceurl'] = ($_SERVER['HTTP_X_ORIGIN_URL']
                ?? $this->requestStack->getParentRequest()?->getSchemeAndHttpHost()
                ?? $this->requestStack->getMainRequest()?->getSchemeAndHttpHost()).'/';
        $parameters['total_timeout'] = 180;
        $parameters['workpath'] = $container->getParameter('kernel.project_dir');
        $parameters['xmlpath'] = $container->getParameter('app.xml_path');

        $parameters['servicenames'] = [
//            'i-sphere.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
//            'www.i-sphere.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
//            'my.i-sphere.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
//            'infosfera.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
//            'www.infosfera.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
//            'lk.infosfera.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
            'my.infohub24.ru' => '<a href="http://infohub24.ru" target="_blank">Инфохаб</a>',
        ];

        return $this->phpEngine->render($view, $parameters);
    }

    protected function render(string $view, array $parameters = [], Response $response = null): Response
    {
        if (!\str_ends_with($view, '.php')) {
            return parent::render($view, $parameters, $response);
        }

        $content = $this->renderView($view, $parameters);
        $response ??= new Response();

        $response->setContent($content);

        return $response;
    }
}
