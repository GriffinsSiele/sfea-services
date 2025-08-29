<?php

declare(strict_types=1);

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Gesdinet\JWTRefreshTokenBundle\GesdinetJWTRefreshTokenBundle;
use Knp\Bundle\MenuBundle\KnpMenuBundle;
use Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle;
use Nelmio\ApiDocBundle\NelmioApiDocBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Sentry\SentryBundle\SentryBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;

return [
    DebugBundle::class => ['dev' => true],
    DoctrineBundle::class => ['all' => true],
    DoctrineMigrationsBundle::class => ['all' => true],
    FrameworkBundle::class => ['all' => true],
    GesdinetJWTRefreshTokenBundle::class => ['all' => true],
    KnpMenuBundle::class => ['all' => true],
    LexikJWTAuthenticationBundle::class => ['all' => true],
    MonologBundle::class => ['all' => true],
    NelmioApiDocBundle::class => ['all' => true],
    SecurityBundle::class => ['all' => true],
    SensioFrameworkExtraBundle::class => ['all' => true],
    SentryBundle::class => ['prod' => true],
    TwigBundle::class => ['all' => true],
    WebProfilerBundle::class => ['dev' => true, 'test' => true],
];
