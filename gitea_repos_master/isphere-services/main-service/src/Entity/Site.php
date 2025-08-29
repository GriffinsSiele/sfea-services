<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SiteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: SiteRepository::class)]
#[Table(name: 'Site')]
class Site
{
    #[Column(type: Types::INTEGER)]
    #[Id]
    #[GeneratedValue]
    private ?int $id = null;

    #[Column]
    private ?string $name = null;

    #[Column]
    private ?string $host = null;

    #[Column]
    private ?string $emailUser = null;

    #[Column]
    private ?string $emailPassword = null;

    #[Column]
    private ?string $emailServer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setHost(?string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setEmailUser(?string $emailUser): self
    {
        $this->emailUser = $emailUser;

        return $this;
    }

    public function getEmailUser(): ?string
    {
        return $this->emailUser;
    }

    public function setEmailPassword(#[\SensitiveParameter] ?string $emailPassword): self
    {
        $this->emailPassword = $emailPassword;

        return $this;
    }

    public function getEmailPassword(): ?string
    {
        return $this->emailPassword;
    }

    public function setEmailServer(?string $emailServer): self
    {
        $this->emailServer = $emailServer;

        return $this;
    }

    public function getEmailServer(): ?string
    {
        return $this->emailServer;
    }
}
