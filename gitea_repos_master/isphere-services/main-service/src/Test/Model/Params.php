<?php

declare(strict_types=1);

namespace App\Test\Model;

use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

class Params
{
    #[SerializedName('user_ip')]
    private ?string $userIp = '';

    #[SerializedName('request_id')]
    private ?string $requestId = '';

    #[SerializedName('request_type')]
    private ?string $requestType = '';

    private ?array $sources = [];

    private ?array $rules = [];

    private ?int $recursive = 0;

    private ?int $async = 0;

    private ?int $timeout = 0;

    private ?array $person = [];

    private ?array $phone = [];

    private ?array $email = [];

    private ?array $nick = [];

    private ?array $url = [];

    private ?array $car = [];

    private ?array $ip = [];

    private ?array $org = [];

    private ?array $card = [];

    #[SerializedName('fssp_ip')]
    private ?array $fsspIp = [];

    private ?array $osago = [];

    private ?array $text = [];

    #[SerializedName('_reqId')]
    private ?string $reqId = '';

    #[SerializedName('_userId')]
    private ?string $userId = '';

    #[SerializedName('_clientId')]
    private ?string $clientId = '';

    #[SerializedName('_reqtime')]
    private ?string $reqTime = '';

    #[SerializedName('_reqdate')]
    private ?string $reqDate = '';

    #[SerializedName('_container')]
    private ?ContainerInterface $container = null;

    #[SerializedName('_logger')]
    private ?LoggerInterface $logger = null;

    #[SerializedName('_contact_types')]
    private ?array $contactTypes = [];

    #[SerializedName('_contact_urls')]
    private ?array $contactUrls = [];

    #[SerializedName('_http_connecttimeout')]
    private ?int $httpConnectTimeout = 0;

    #[SerializedName('_http_timeout')]
    private ?int $httpTimeout = 0;

    #[SerializedName('_http_agent')]
    private ?string $httpAgent = '';

    #[SerializedName('_user_sources')]
    private ?array $userSources = [];

    #[SerializedName('_connection')]
    private ?Connection $connection = null;

    #[SerializedName('_cbrConnection')]
    private ?Connection $cbrConnection = null;

    #[SerializedName('_fnsConnection')]
    private ?Connection $fnsConnection = null;

    #[SerializedName('_xmlpath')]
    private ?string $xmlPath = '';

    #[SerializedName('_serviceurl')]
    private ?string $serviceUrl = '';

    #[SerializedName('_req')]
    private ?string $req = '';

    public function toArray(): array
    {
        return [
            'user_ip' => $this->getUserIp(),
            'request_id' => $this->getRequestId(),
            'request_type' => $this->getRequestType(),
            'sources' => $this->getSources(),
            'rules' => $this->getRules(),
            'recursive' => $this->getRecursive(),
            'async' => $this->getAsync(),
            'timeout' => $this->getTimeout(),
            'person' => $this->getPerson(),
            'phone' => $this->getPhone(),
            'email' => $this->getEmail(),
            'nick' => $this->getNick(),
            'url' => $this->getUrl(),
            'car' => $this->getCar(),
            'ip' => $this->getIp(),
            'org' => $this->getOrg(),
            'card' => $this->getCard(),
            'fssp_ip' => $this->getFsspIp(),
            'osago' => $this->getOsago(),
            'text' => $this->getText(),
            '_reqId' => $this->getReqId(),
            '_userId' => $this->getUserId(),
            '_clientId' => $this->getClientId(),
            '_container' => $this->getContainer(),
            '_logger' => $this->getLogger(),
            '_contact_types' => $this->getContactTypes(),
            '_contact_urls' => $this->getContactUrls(),
            '_http_connecttimeout' => $this->getHttpConnectTimeout(),
            '_http_timeout' => $this->getHttpTimeout(),
            '_http_agent' => $this->getHttpAgent(),
            '_user_sources' => $this->getUserSources(),
            '_connection' => $this->getConnection(),
            '_cbrConnection' => $this->getCbrConnection(),
            '_fnsConnection' => $this->getFnsConnection(),
            '_reqdate' => $this->getReqDate(),
            '_reqtime' => $this->getReqTime(),
            '_xmlpath' => $this->getXmlPath(),
            '_serviceurl' => $this->getServiceUrl(),
            '_req' => $this->getReq(),
        ];
    }

    public function setUserIp(?string $userIp): self
    {
        $this->userIp = $userIp;

        return $this;
    }

    public function getUserIp(): ?string
    {
        return $this->userIp;
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

    public function getSources(): ?array
    {
        return $this->sources;
    }

    public function setRules(?array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    public function getRules(): ?array
    {
        return $this->rules;
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

    public function setTimeout(?int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    public function setPerson(?array $person): self
    {
        $this->person = $person;

        return $this;
    }

    public function getPerson(): ?array
    {
        return $this->person;
    }

    public function setPhone(?array $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPhone(): ?array
    {
        return $this->phone;
    }

    public function setEmail(?array $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): ?array
    {
        return $this->email;
    }

    public function setNick(?array $nick): self
    {
        $this->nick = $nick;

        return $this;
    }

    public function getNick(): ?array
    {
        return $this->nick;
    }

    public function setUrl(?array $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): ?array
    {
        return $this->url;
    }

    public function setCar(?array $car): self
    {
        $this->car = $car;

        return $this;
    }

    public function getCar(): ?array
    {
        return $this->car;
    }

    public function setIp(?array $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getIp(): ?array
    {
        return $this->ip;
    }

    public function setOrg(?array $org): self
    {
        $this->org = $org;

        return $this;
    }

    public function getOrg(): ?array
    {
        return $this->org;
    }

    public function setCard(?array $card): self
    {
        $this->card = $card;

        return $this;
    }

    public function getCard(): ?array
    {
        return $this->card;
    }

    public function setFsspIp(?array $fsspIp): self
    {
        $this->fsspIp = $fsspIp;

        return $this;
    }

    public function getFsspIp(): ?array
    {
        return $this->fsspIp;
    }

    public function setOsago(?array $osago): self
    {
        $this->osago = $osago;

        return $this;
    }

    public function getOsago(): ?array
    {
        return $this->osago;
    }

    public function setText(?array $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getText(): ?array
    {
        return $this->text;
    }

    public function setReqId(?string $reqId): self
    {
        $this->reqId = $reqId;

        return $this;
    }

    public function getReqId(): ?string
    {
        return $this->reqId;
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

    public function setClientId(?string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setContainer(?ContainerInterface $container): self
    {
        $this->container = $container;

        return $this;
    }

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    public function setLogger(?LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function setContactTypes(?array $contactTypes): self
    {
        $this->contactTypes = $contactTypes;

        return $this;
    }

    public function getContactTypes(): ?array
    {
        return $this->contactTypes;
    }

    public function setContactUrls(?array $contactUrls): self
    {
        $this->contactUrls = $contactUrls;

        return $this;
    }

    public function getContactUrls(): ?array
    {
        return $this->contactUrls;
    }

    public function setHttpConnectTimeout(?int $httpConnectTimeout): self
    {
        $this->httpConnectTimeout = $httpConnectTimeout;

        return $this;
    }

    public function getHttpConnectTimeout(): ?int
    {
        return $this->httpConnectTimeout;
    }

    public function setHttpTimeout(?int $httpTimeout): self
    {
        $this->httpTimeout = $httpTimeout;

        return $this;
    }

    public function getHttpTimeout(): ?int
    {
        return $this->httpTimeout;
    }

    public function setHttpAgent(?string $httpAgent): self
    {
        $this->httpAgent = $httpAgent;

        return $this;
    }

    public function getHttpAgent(): ?string
    {
        return $this->httpAgent;
    }

    public function setUserSources(?array $userSources): self
    {
        $this->userSources = $userSources;

        return $this;
    }

    public function getUserSources(): ?array
    {
        return $this->userSources;
    }

    public function setConnection(?Connection $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function getConnection(): ?Connection
    {
        return $this->connection;
    }

    public function setCbrConnection(?Connection $cbrConnection): self
    {
        $this->cbrConnection = $cbrConnection;

        return $this;
    }

    public function getCbrConnection(): ?Connection
    {
        return $this->cbrConnection;
    }

    public function setFnsConnection(?Connection $fnsConnection): self
    {
        $this->fnsConnection = $fnsConnection;

        return $this;
    }

    public function getFnsConnection(): ?Connection
    {
        return $this->fnsConnection;
    }

    public function setReqTime(?string $reqTime): self
    {
        $this->reqTime = $reqTime;

        return $this;
    }

    public function getReqTime(): ?string
    {
        return $this->reqTime;
    }

    public function setReqDate(?string $reqDate): self
    {
        $this->reqDate = $reqDate;

        return $this;
    }

    public function getReqDate(): ?string
    {
        return $this->reqDate;
    }

    public function setXmlPath(?string $xmlPath): self
    {
        $this->xmlPath = $xmlPath;

        return $this;
    }

    public function getXmlPath(): ?string
    {
        return $this->xmlPath;
    }

    public function setServiceUrl(?string $serviceUrl): self
    {
        $this->serviceUrl = $serviceUrl;

        return $this;
    }

    public function getServiceUrl(): ?string
    {
        return $this->serviceUrl;
    }

    public function setReq(?string $req): self
    {
        $this->req = $req;

        return $this;
    }

    public function getReq(): ?string
    {
        return $this->req;
    }
}
