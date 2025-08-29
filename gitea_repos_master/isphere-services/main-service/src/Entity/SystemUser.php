<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SystemUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[Entity(repositoryClass: SystemUserRepository::class)]
#[Table(name: 'SystemUsers')]
class SystemUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[Column(name: 'Id', type: Types::INTEGER)]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[OneToOne(targetEntity: Access::class)]
    #[JoinColumn(name: 'AccessLevel', referencedColumnName: 'Level')]
    private ?Access $access = null;

    #[Column(name: 'Login', length: 50)]
    private ?string $login = null;

    #[Column(name: 'Password', length: 100)]
    private ?string $password = null;

    #[Column(name: 'Locked', type: Types::INTEGER, options: ['default' => 0])]
    private ?int $locked = null;

    #[Column(name: 'Deleted', type: Types::INTEGER, nullable: true)]
    private ?int $deleted = null;

    #[Column(name: 'AccessLevel', type: Types::INTEGER, options: ['default' => 0])]
    private ?int $accessLevel = null;

    #[Column(name: 'AccessArea', type: Types::INTEGER, options: ['default' => 0])]
    private ?int $accessArea = null;

    #[Column(name: 'ResultsArea', type: Types::INTEGER, nullable: true)]
    private ?int $resultsArea = null;

    #[Column(name: 'ReportsArea', type: Types::INTEGER, nullable: true)]
    private ?int $reportsArea = null;

    #[Column(name: 'MasterUserId', type: Types::INTEGER, nullable: true)]
    private ?int $masterUserId = null;

    #[Column(name: 'Email', length: 100, options: ['default' => ''])]
    private ?string $email = null;

    #[Column(name: 'Phone', length: 100, options: ['default' => ''])]
    private ?string $phone = null;

    #[Column(name: 'OrgName', length: 100, options: ['default' => ''])]
    private ?string $orgName = null;

    #[Column(name: 'SiteId', type: Types::INTEGER, nullable: true)]
    private ?int $siteId = null;

    #[Column(name: 'ClientId', type: Types::INTEGER, nullable: true)]
    private ?int $clientId = null;

    #[ManyToOne(targetEntity: Client::class)]
    #[JoinColumn(name: 'ClientId')]
    private ?Client $client = null;

    #[Column(name: 'Created', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeInterface $created = null;

    #[Column(name: 'DefaultPrice', type: Types::FLOAT, precision: 5, scale: 3, nullable: true)]
    private ?float $defaultPrice = null;

    #[Column(name: 'AllowedIP', length: 100, nullable: true)]
    private ?string $allowedIp = null;

    #[Column(name: 'LastTime', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastTime = null;

    #[Column(name: 'EndTime', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeInterface $endTime = null;

    #[Column(name: 'MessageId', type: Types::INTEGER, nullable: true)]
    private ?int $messageId = null;

    #[ManyToOne(targetEntity: Message::class)]
    #[JoinColumn(name: 'MessageId')]
    private ?Message $message = null;

    #[Column(name: 'CallbackURL', length: 100, nullable: true)]
    private ?string $callbackUrl = null;

    #[Column(name: 'CallbackUser', length: 20, nullable: true)]
    private ?string $callbackUser = null;

    #[Column(name: 'CallbackPassword', length: 20, nullable: true)]
    private ?string $callbackPassword = null;

    #[ManyToMany(targetEntity: AccessSource::class)]
    #[JoinTable('AccessSource')]
    #[JoinColumn(name: 'Level', referencedColumnName: 'AccessLevel')]
    #[InverseJoinColumn(name: 'Level', referencedColumnName: 'Level')]
    private ?Collection $accessSources = null;

    #[ManyToMany(targetEntity: AccessRule::class)]
    #[JoinTable('AccessRule')]
    #[JoinColumn(name: 'Level', referencedColumnName: 'AccessLevel')]
    #[InverseJoinColumn(name: 'Level', referencedColumnName: 'Level')]
    private ?Collection $accessRules = null;

    public function __construct()
    {
        $this->accessSources = new ArrayCollection();
        $this->accessRules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setAccess(?Access $access): self
    {
        $this->access = $access;

        return $this;
    }

    public function getAccess(): ?Access
    {
        return $this->access;
    }

    public function setLogin(?string $login): self
    {
        $this->login = $login;

        return $this;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setPassword(#[\SensitiveParameter] ?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setLocked(?int $locked): self
    {
        $this->locked = $locked;

        return $this;
    }

    public function getLocked(): ?int
    {
        return $this->locked;
    }

    public function setDeleted(?int $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getDeleted(): ?int
    {
        return $this->deleted;
    }

    public function setAccessLevel(?int $accessLevel): self
    {
        $this->accessLevel = $accessLevel;

        return $this;
    }

    public function getAccessLevel(): ?int
    {
        return $this->accessLevel;
    }

    public function setAccessArea(?int $accessArea): self
    {
        $this->accessArea = $accessArea;

        return $this;
    }

    public function getAccessArea(): ?int
    {
        return $this->accessArea ?: $this->getAccessArea();
    }

    public function setResultsArea(?int $resultsArea): self
    {
        $this->resultsArea = $resultsArea;

        return $this;
    }

    public function getResultsArea(): ?int
    {
        return $this->resultsArea;
    }

    public function setReportsArea(?int $reportsArea): self
    {
        $this->reportsArea = $reportsArea;

        return $this;
    }

    public function getReportsArea(): ?int
    {
        return $this->reportsArea;
    }

    public function setMasterUserId(?int $masterUserId): self
    {
        $this->masterUserId = $masterUserId;

        return $this;
    }

    public function getMasterUserId(): ?int
    {
        return $this->masterUserId;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setOrgName(?string $orgName): self
    {
        $this->orgName = $orgName;

        return $this;
    }

    public function getOrgName(): ?string
    {
        return $this->orgName;
    }

    public function setSiteId(?int $siteId): self
    {
        $this->siteId = $siteId;

        return $this;
    }

    public function getSiteId(): ?int
    {
        return $this->siteId;
    }

    public function setClientId(?int $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getClientId(): ?int
    {
        return $this->clientId;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setCreated(?\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setDefaultPrice(?float $defaultPrice): self
    {
        $this->defaultPrice = $defaultPrice;

        return $this;
    }

    public function getDefaultPrice(): ?float
    {
        return $this->defaultPrice;
    }

    public function setAllowedIp(?string $allowedIp): self
    {
        $this->allowedIp = $allowedIp;

        return $this;
    }

    public function getAllowedIp(): ?string
    {
        return $this->allowedIp;
    }

    public function setLastTime(?\DateTimeInterface $lastTime): self
    {
        $this->lastTime = $lastTime;

        return $this;
    }

    public function getLastTime(): ?\DateTimeInterface
    {
        return $this->lastTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setMessageId(?int $messageId): self
    {
        $this->messageId = $messageId;

        return $this;
    }

    public function getMessageId(): ?int
    {
        return $this->messageId;
    }

    public function setMessage(?Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): ?Message
    {
        return $this->message ?? $this->getClient()?->getMessage();
    }

    public function setCallbackUrl(?string $callbackUrl): self
    {
        $this->callbackUrl = $callbackUrl;

        return $this;
    }

    public function getCallbackUrl(): ?string
    {
        return $this->callbackUrl;
    }

    public function setCallbackUser(?string $callbackUser): self
    {
        $this->callbackUser = $callbackUser;

        return $this;
    }

    public function getCallbackUser(): ?string
    {
        return $this->callbackUser;
    }

    public function setCallbackPassword(?string $callbackPassword): self
    {
        $this->callbackPassword = $callbackPassword;

        return $this;
    }

    public function getCallbackPassword(): ?string
    {
        return $this->callbackPassword;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->getLogin();
    }

    public function getRoles(): array
    {
        $roles = [
            AccessRoles::ROLE_USER,
        ];

        foreach ($this->getAccess()?->getRoles() as $accessRole) {
            $roles[] = $accessRole;
        }

        return $roles;
    }

    public function addAccessSource(AccessSource $accessSource): self
    {
        $this->accessSources->add($accessSource);

        return $this;
    }

    public function hasAccessSourceBySourceName(string $sourceName): bool
    {
        return null !== $this->getAccessSources()?->findFirst(
            static fn ($_, AccessSource $accessSource): bool => $accessSource->getSourceName() === $sourceName,
        );
    }

    public function removeAccessSource(AccessSource $accessSource): self
    {
        $this->accessSources->removeElement($accessSource);

        return $this;
    }

    /**
     * @return Collection|AccessSource[]
     */
    public function getAccessSources(): ?Collection
    {
        return $this->accessSources->filter(
            static fn (AccessSource $accessSource): bool => (bool) $accessSource->getAllowed(),
        );
    }

    public function getAccessSourcesMap(): array
    {
        $result = [];

        foreach ($this->getAccessSources() as $accessSource) {
            $result[$accessSource->getSourceName()] = true;
        }

        return $result;
    }

    public function addAccessRule(AccessRule $accessRule): self
    {
        $this->accessRules->add($accessRule);

        return $this;
    }

    public function hasAccessRuleByRuleName(string $ruleName): bool
    {
        return null !== $this->getAccessRules()?->findFirst(
            static fn ($_, AccessRule $accessRule): bool => $accessRule->getRuleName() === $ruleName,
        );
    }

    public function removeAccessRule(AccessRule $accessRule): self
    {
        $this->accessRules->removeElement($accessRule);

        return $this;
    }

    /**
     * @return Collection|AccessRule[]
     */
    public function getAccessRules(): ?Collection
    {
        return $this->accessRules->filter(
            static fn (AccessRule $accessRule): bool => (bool) $accessRule->getAllowed(),
        );
    }
}
