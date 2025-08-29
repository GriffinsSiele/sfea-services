<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AccessRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity(repositoryClass: AccessRepository::class)]
#[Table(name: 'Access')]
class Access
{
    #[Column(name: 'Level', type: Types::INTEGER)]
    #[Id]
    private ?int $level = null;

    #[Column(name: 'Level_old', type: Types::INTEGER, nullable: true)]
    private ?int $levelOld = null;

    #[Column(type: Types::INTEGER, nullable: true)]
    private ?int $check = null;

    #[Column(name: 'checkorg', type: Types::INTEGER, nullable: true)]
    private ?int $checkOrg = null;

    #[Column(name: 'checkphone', type: Types::INTEGER, nullable: true)]
    private ?int $checkPhone = null;

    #[Column(name: 'checkphone_kz', type: Types::INTEGER, nullable: true)]
    private ?int $checkPhoneKz = null;

    #[Column(name: 'checkphone_uz', type: Types::INTEGER, nullable: true)]
    private ?int $checkPhoneUz = null;

    #[Column(name: 'checkphone_bg', type: Types::INTEGER, nullable: true)]
    private ?int $checkPhoneBg = null;

    #[Column(name: 'checkphone_ro', type: Types::INTEGER, nullable: true)]
    private ?int $checkPhoneRo = null;

    #[Column(name: 'checkphone_pl', type: Types::INTEGER, nullable: true)]
    private ?int $checkPhonePl = null;

    #[Column(name: 'checkphone_pt', type: Types::INTEGER, nullable: true)]
    private ?int $checkPhonePt = null;

    #[Column(name: 'checkemail', type: Types::INTEGER, nullable: true)]
    private ?int $checkEmail = null;

    #[Column(name: 'checkurl', type: Types::INTEGER, nullable: true)]
    private ?int $checkUrl = null;

    #[Column(name: 'checktext', type: Types::INTEGER, nullable: true)]
    private ?int $checkText = null;

    #[Column(name: 'checkskype', type: Types::INTEGER, nullable: true)]
    private ?int $checkSkype = null;

    #[Column(name: 'checkauto', type: Types::INTEGER, nullable: true)]
    private ?int $checkAuto = null;

    #[Column(name: 'checkip', type: Types::INTEGER, nullable: true)]
    private ?int $checkIp = null;

    #[Column(name: 'checkcard', type: Types::INTEGER, nullable: true)]
    private ?int $checkCard = null;

    #[Column(name: 'chey', type: Types::INTEGER, nullable: true)]
    private ?int $checkChey = null;

    #[Column(name: 'history', type: Types::INTEGER, nullable: true)]
    private ?int $checkHistory = null;

    #[Column(name: 'reports', type: Types::INTEGER, nullable: true)]
    private ?int $checkReports = null;

    #[Column(name: 'news', type: Types::INTEGER, nullable: true)]
    private ?int $checkNews = null;

    #[Column(name: 'sources', type: Types::INTEGER, nullable: true)]
    private ?int $checkSources = null;

    #[Column(name: 'rules', type: Types::INTEGER, nullable: true)]
    private ?int $checkRules = null;

    #[Column(name: 'bulk', type: Types::INTEGER, nullable: true)]
    private ?int $checkBulk = null;

    #[Column(name: 'stats', type: Types::INTEGER, nullable: true)]
    private ?int $checkStats = null;

    #[Column(name: 'users', type: Types::INTEGER, nullable: true)]
    private ?int $checkUsers = null;

    public function setLevel(?int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevelOld(?int $levelOld): self
    {
        $this->levelOld = $levelOld;

        return $this;
    }

    public function getLevelOld(): ?int
    {
        return $this->levelOld;
    }

    public function setCheck(?int $check): self
    {
        $this->check = $check;

        return $this;
    }

    public function getCheck(): ?int
    {
        return $this->check;
    }

    public function setCheckOrg(?int $checkOrg): self
    {
        $this->checkOrg = $checkOrg;

        return $this;
    }

    public function getCheckOrg(): ?int
    {
        return $this->checkOrg;
    }

    public function setCheckPhone(?int $checkPhone): self
    {
        $this->checkPhone = $checkPhone;

        return $this;
    }

    public function getCheckPhone(): ?int
    {
        return $this->checkPhone;
    }

    public function setCheckPhoneKz(?int $checkPhoneKz): self
    {
        $this->checkPhoneKz = $checkPhoneKz;

        return $this;
    }

    public function getCheckPhoneKz(): ?int
    {
        return $this->checkPhoneKz;
    }

    public function setCheckPhoneUz(?int $checkPhoneUz): self
    {
        $this->checkPhoneUz = $checkPhoneUz;

        return $this;
    }

    public function getCheckPhoneUz(): ?int
    {
        return $this->checkPhoneUz;
    }

    public function setCheckPhoneBg(?int $checkPhoneBg): self
    {
        $this->checkPhoneBg = $checkPhoneBg;

        return $this;
    }

    public function getCheckPhoneBg(): ?int
    {
        return $this->checkPhoneBg;
    }

    public function setCheckPhoneRo(?int $checkPhoneRo): self
    {
        $this->checkPhoneRo = $checkPhoneRo;

        return $this;
    }

    public function getCheckPhoneRo(): ?int
    {
        return $this->checkPhoneRo;
    }

    public function setCheckPhonePl(?int $checkPhonePl): self
    {
        $this->checkPhonePl = $checkPhonePl;

        return $this;
    }

    public function getCheckPhonePl(): ?int
    {
        return $this->checkPhonePl;
    }

    public function setCheckPhonePt(?int $checkPhonePt): self
    {
        $this->checkPhonePt = $checkPhonePt;

        return $this;
    }

    public function getCheckPhonePt(): ?int
    {
        return $this->checkPhonePt;
    }

    public function setCheckEmail(?int $checkEmail): self
    {
        $this->checkEmail = $checkEmail;

        return $this;
    }

    public function getCheckEmail(): ?int
    {
        return $this->checkEmail;
    }

    public function setCheckUrl(?int $checkUrl): self
    {
        $this->checkUrl = $checkUrl;

        return $this;
    }

    public function getCheckUrl(): ?int
    {
        return $this->checkUrl;
    }

    public function setCheckText(?int $checkText): self
    {
        $this->checkText = $checkText;

        return $this;
    }

    public function getCheckText(): ?int
    {
        return $this->checkText;
    }

    public function setCheckSkype(?int $checkSkype): self
    {
        $this->checkSkype = $checkSkype;

        return $this;
    }

    public function getCheckSkype(): ?int
    {
        return $this->checkSkype;
    }

    public function setCheckAuto(?int $checkAuto): self
    {
        $this->checkAuto = $checkAuto;

        return $this;
    }

    public function getCheckAuto(): ?int
    {
        return $this->checkAuto;
    }

    public function setCheckIp(?int $checkIp): self
    {
        $this->checkIp = $checkIp;

        return $this;
    }

    public function getCheckIp(): ?int
    {
        return $this->checkIp;
    }

    public function setCheckCard(?int $checkCard): self
    {
        $this->checkCard = $checkCard;

        return $this;
    }

    public function getCheckCard(): ?int
    {
        return $this->checkCard;
    }

    public function setCheckChey(?int $checkChey): self
    {
        $this->checkChey = $checkChey;

        return $this;
    }

    public function getCheckChey(): ?int
    {
        return $this->checkChey;
    }

    public function setCheckHistory(?int $checkHistory): self
    {
        $this->checkHistory = $checkHistory;

        return $this;
    }

    public function getCheckHistory(): ?int
    {
        return $this->checkHistory;
    }

    public function setCheckReports(?int $checkReports): self
    {
        $this->checkReports = $checkReports;

        return $this;
    }

    public function getCheckReports(): ?int
    {
        return $this->checkReports;
    }

    public function setCheckNews(?int $checkNews): self
    {
        $this->checkNews = $checkNews;

        return $this;
    }

    public function getCheckNews(): ?int
    {
        return $this->checkNews;
    }

    public function setCheckSources(?int $checkSources): self
    {
        $this->checkSources = $checkSources;

        return $this;
    }

    public function getCheckSources(): ?int
    {
        return $this->checkSources;
    }

    public function setCheckRules(?int $checkRules): self
    {
        $this->checkRules = $checkRules;

        return $this;
    }

    public function getCheckRules(): ?int
    {
        return $this->checkRules;
    }

    public function setCheckBulk(?int $checkBulk): self
    {
        $this->checkBulk = $checkBulk;

        return $this;
    }

    public function getCheckBulk(): ?int
    {
        return $this->checkBulk;
    }

    public function setCheckStats(?int $checkStats): self
    {
        $this->checkStats = $checkStats;

        return $this;
    }

    public function getCheckStats(): ?int
    {
        return $this->checkStats;
    }

    public function setCheckUsers(?int $checkUsers): self
    {
        $this->checkUsers = $checkUsers;

        return $this;
    }

    public function getCheckUsers(): ?int
    {
        return $this->checkUsers;
    }

    public function getRoles(): iterable
    {
        if ($this->getCheck()) {
            yield AccessRoles::ROLE_CHECK;
        }

        if ($this->getCheckOrg()) {
            yield AccessRoles::ROLE_CHECK_ORG;
        }

        if ($this->getCheckPhone()) {
            yield AccessRoles::ROLE_CHECK_PHONE;
        }

        if ($this->getCheckPhoneKz()) {
            yield AccessRoles::ROLE_CHECK_PHONE_KZ;
        }

        if ($this->getCheckPhoneUz()) {
            yield AccessRoles::ROLE_CHECK_PHONE_UZ;
        }

        if ($this->getCheckPhoneBg()) {
            yield AccessRoles::ROLE_CHECK_PHONE_BG;
        }

        if ($this->getCheckPhoneRo()) {
            yield AccessRoles::ROLE_CHECK_PHONE_RO;
        }

        if ($this->getCheckPhonePl()) {
            yield AccessRoles::ROLE_CHECK_PHONE_PL;
        }

        if ($this->getCheckPhonePt()) {
            yield AccessRoles::ROLE_CHECK_PHONE_PT;
        }

        if ($this->getCheckEmail()) {
            yield AccessRoles::ROLE_CHECK_EMAIL;
        }

        if ($this->getCheckUrl()) {
            yield AccessRoles::ROLE_CHECK_URL;
        }

        if ($this->getCheckText()) {
            yield AccessRoles::ROLE_CHECK_TEXT;
        }

        if ($this->getCheckSkype()) {
            yield AccessRoles::ROLE_CHECK_SKYPE;
        }

        if ($this->getCheckAuto()) {
            yield AccessRoles::ROLE_CHECK_AUTO;
        }

        if ($this->getCheckIp()) {
            yield AccessRoles::ROLE_CHECK_IP;
        }

        if ($this->getCheckCard()) {
            yield AccessRoles::ROLE_CHECK_CARD;
        }

        if ($this->getCheckChey()) {
            yield AccessRoles::ROLE_CHECK_CHEY;
        }

        if ($this->getCheckHistory()) {
            yield AccessRoles::ROLE_CHECK_HISTORY;
        }

        if ($this->getCheckReports()) {
            yield AccessRoles::ROLE_CHECK_REPORTS;
        }

        if ($this->getCheckNews()) {
            yield AccessRoles::ROLE_CHECK_NEWS;
        }

        if ($this->getCheckSources()) {
            yield AccessRoles::ROLE_CHECK_SOURCES;
        }

        if ($this->getCheckRules()) {
            yield AccessRoles::ROLE_CHECK_RULES;
        }

        if ($this->getCheckBulk()) {
            yield AccessRoles::ROLE_CHECK_BULK;
        }

        if ($this->getCheckStats()) {
            yield AccessRoles::ROLE_CHECK_STATS;
        }

        if ($this->getCheckUsers()) {
            yield AccessRoles::ROLE_CHECK_USERS;
        }
    }
}
