<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as HttpKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends HttpKernel
{
    use MicroKernelTrait;

    public static function getInstance(): self
    {
        $kernel = $GLOBALS['app'];

        if ($kernel instanceof Application) {
            $kernel = $kernel->getKernel();
        }

        if (!$kernel instanceof self) {
            throw new \RuntimeException('$kernel is not a kernel, '.$kernel::class.' provided');
        }

        return $kernel;
    }

    public function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/{packages}/*.yaml');
        $container->import('../config/{packages}/'.$this->environment.'/*.yaml');
        $container->import('../config/services.yaml');
    }

    public function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/{routes}/'.$this->environment.'/*.yaml');
        $routes->import('../config/{routes}/*.yaml');
        $routes->import('../config/routes.yaml');
    }
}
