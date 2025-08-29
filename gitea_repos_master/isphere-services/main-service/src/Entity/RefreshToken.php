<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RefreshTokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[Entity(repositoryClass: RefreshTokenRepository::class)]
#[Table(name: 'RefreshToken')]
class RefreshToken extends BaseRefreshToken
{
    #[Column(type: Types::INTEGER)]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[Column]
    protected $refreshToken;

    #[Column]
    protected $username;

    #[Column(type: Types::DATETIME_MUTABLE)]
    protected $valid;
}
