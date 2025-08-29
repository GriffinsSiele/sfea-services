<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints\NotBlank;

class Request
{
    public const DEFAULT_TIMEOUT = 30; // seconds

    #[SerializedName('UserIP')]
    private ?string $userIp = null;

    #[SerializedName('UserID')]
    #[NotBlank]
    private ?string $userId = null;

    #[SerializedName('Password')]
    private ?string $password = null;

    #[SerializedName('requestId')]
    private ?string $requestId = null;

    #[SerializedName('requestType')]
    private ?string $requestType = null;

    /**
     * @see Request::getNormalizedSources()
     */
    #[Ignore]
    private ?array $sources = null;

    #[SerializedName('rules')]
    private ?array $rules = null;

    #[SerializedName('timeout')]
    private ?int $timeout = self::DEFAULT_TIMEOUT;

    #[SerializedName('recursive')]
    private ?int $recursive = null;

    #[SerializedName('async')]
    private ?int $async = null;

    /**
     * @var CardReq[]|null
     */
    #[SerializedName('CardReq')]
    private ?array $cards = null;

    /**
     * @var CarReq[]|null
     */
    #[SerializedName('CarReq')]
    private ?array $cars = null;

    /**
     * @var EmailReq[]|null
     */
    #[SerializedName('EmailReq')]
    private ?array $emails = null;

    /**
     * @var IPReq[]|null
     */
    #[SerializedName('IPReq')]
    private ?array $ips = null;

    /**
     * @var OrgReq[]|null
     */
    #[SerializedName('OrgReq')]
    private ?array $orgs = null;

    /**
     * @var PersonReq[]|null
     */
    #[SerializedName('PersonReq')]
    private ?array $persons = null;

    /**
     * @var PhoneReq[]|null
     */
    #[SerializedName('PhoneReq')]
    private ?array $phones = null;

    /**
     * @var SkypeReq[]|null
     */
    #[SerializedName('SkypeReq')]
    private ?array $skypes = null;

    /**
     * @var OtherReq[]|null
     */
    #[SerializedName('OtherReq')]
    private ?array $others = null;

    /**
     * @var URLReq[]|null
     */
    #[SerializedName('URLReq')]
    private ?array $urls = null;

    #[SerializedName('requestDateTime')]
    private ?\DateTimeInterface $requestDateTime = null;

    public function setUserIp(?string $userIp): self
    {
        $this->userIp = $userIp;

        return $this;
    }

    public function getUserIp(): ?string
    {
        return $this->userIp;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setRequestId(?string $requestId): self
    {
        $this->requestId = $requestId;

        return $this;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function setRequestType(?string $requestType): self
    {
        $this->requestType = $requestType;

        return $this;
    }

    public function getRequestType(): ?string
    {
        return $this->requestType;
    }

    public function setSources(?array $sources): self
    {
        $this->sources = $sources;

        return $this;
    }

    public function addSource(string $source): self
    {
        $this->sources ??= [];
        $this->sources[] = $source;

        return $this;
    }

    public function getSources(): ?array
    {
        return $this->sources;
    }

    public function setNormalizedSources(?string $sources): self
    {
        if (null === $sources) {
            $this->sources = null;
        } else {
            $this->sources = \explode(',', $sources);
        }

        return $this;
    }

    #[SerializedName('sources')]
    public function getNormalizedSources(): ?string
    {
        if (empty($this->sources)) {
            return null;
        }

        return \implode(',', $this->sources);
    }

    public function setRules(?array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    public function addRule(string $rule): self
    {
        $this->rules ??= [];
        $this->rules[] = $rule;

        return $this;
    }

    public function getRules(): ?array
    {
        return $this->rules;
    }

    public function setTimeout(?int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    public function setRecursive(?int $recursive): self
    {
        $this->recursive = $recursive;

        return $this;
    }

    public function getRecursive(): ?int
    {
        return $this->recursive;
    }

    public function setAsync(?int $async): self
    {
        $this->async = $async;

        return $this;
    }

    public function getAsync(): ?int
    {
        return $this->async;
    }

    public function setCards(?array $cards): self
    {
        $this->cards = $cards;

        return $this;
    }

    public function addCard(CardReq $card): self
    {
        $this->cards ??= [];
        $this->cards[] = $card;

        return $this;
    }

    public function getCards(): ?array
    {
        return $this->cards;
    }

    public function setCars(?array $cars): self
    {
        $this->cars = $cars;

        return $this;
    }

    public function addCar(CarReq $car): self
    {
        $this->cars ??= [];
        $this->cars[] = $car;

        return $this;
    }

    public function getCars(): ?array
    {
        return $this->cars;
    }

    public function setPersons(?array $persons): self
    {
        $this->persons = $persons;

        return $this;
    }

    public function addPerson(PersonReq $person): self
    {
        $this->persons ??= [];
        $this->persons[] = $person;

        return $this;
    }

    public function getPersons(): ?array
    {
        return $this->persons;
    }

    #[Ignore]
    public function getFirstPerson(): ?PersonReq
    {
        if (null === $this->persons) {
            return null;
        }

        return \reset($this->persons);
    }

    public function setFirstPerson(PersonReq $person): self
    {
        $this->persons ??= [];
        $this->persons[0] = $person;

        return $this;
    }

    public function setPhones(?array $phones): self
    {
        $this->phones = $phones;

        return $this;
    }

    public function addPhone(PhoneReq $phone): self
    {
        $this->phones ??= [];
        $this->phones[] = $phone;

        return $this;
    }

    public function getPhones(): ?array
    {
        return $this->phones;
    }

    #[Ignore]
    public function getFirstPhone(): ?PhoneReq
    {
        if (null === $this->phones) {
            return null;
        }

        return \reset($this->phones);
    }

    public function setFirstPhone(PhoneReq $phone): self
    {
        $this->phones ??= [];
        $this->phones[0] = $phone;

        return $this;
    }

    public function setEmails(?array $emails): self
    {
        $this->emails = $emails;

        return $this;
    }

    public function addEmail(EmailReq $email): self
    {
        $this->emails ??= [];
        $this->emails[] = $email;

        return $this;
    }

    public function getEmails(): ?array
    {
        return $this->emails;
    }

    #[Ignore]
    public function getFirstEmail(): ?EmailReq
    {
        if (null === $this->emails) {
            return null;
        }

        return \reset($this->emails);
    }

    public function setFirstEmail(EmailReq $email): self
    {
        $this->emails ??= [];
        $this->emails[0] = $email;

        return $this;
    }

    public function setIps(?array $ips): self
    {
        $this->ips = $ips;

        return $this;
    }

    public function addIp(IPReq $ip): self
    {
        $this->ips ??= [];
        $this->ips[] = $ip;

        return $this;
    }

    public function getIps(): ?array
    {
        return $this->ips;
    }

    public function setOrgs(?array $orgs): self
    {
        $this->orgs = $orgs;

        return $this;
    }

    public function addOrg(OrgReq $org): self
    {
        $this->orgs ??= [];
        $this->orgs[] = $org;

        return $this;
    }

    public function getOrgs(): ?array
    {
        return $this->orgs;
    }

    public function setSkypes(?array $skypes): self
    {
        $this->skypes = $skypes;

        return $this;
    }

    public function addSkype(SkypeReq $skype): self
    {
        $this->skypes ??= [];
        $this->skypes[] = $skype;

        return $this;
    }

    public function getSkypes(): ?array
    {
        return $this->skypes;
    }

    public function setOthers(?array $others): self
    {
        $this->others = $others;

        return $this;
    }

    public function addOther(OtherReq $other): self
    {
        $this->others ??= [];
        $this->others[] = $other;

        return $this;
    }

    public function getOthers(): ?array
    {
        return $this->others;
    }

    public function setUrls(?array $urls): self
    {
        $this->urls = $urls;

        return $this;
    }

    public function addUrl(URLReq $url): self
    {
        $this->urls ??= [];
        $this->urls[] = $url;

        return $this;
    }

    public function getUrls(): ?array
    {
        return $this->urls;
    }

    public function setRequestDateTime(?\DateTimeInterface $requestDateTime): self
    {
        $this->requestDateTime = $requestDateTime;

        return $this;
    }

    public function getRequestDateTime(): ?\DateTimeInterface
    {
        return $this->requestDateTime;
    }

    public function forEveryReq(): iterable
    {
        $methods = \get_class_methods(self::class);
        $filtered = \array_filter($methods, static fn ($method) => \str_starts_with($method, 'add'));
        $getters = \array_map(static function ($method) use ($methods) {
            $mask = \str_replace('add', 'get', $method);

            foreach ($methods as $m) {
                if (\str_starts_with($m, $mask)) {
                    return $m;
                }
            }

            throw new \RuntimeException('No find getter for '.$method);
        }, $filtered);

        foreach ($getters as $getter) {
            foreach ($this->$getter() ?? [] as $element) {
                if (!empty($element)) {
                    yield $element;
                }
            }
        }
    }
}
