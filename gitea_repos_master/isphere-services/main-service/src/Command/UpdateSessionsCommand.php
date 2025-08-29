<?php

declare(strict_types=1);

namespace App\Command;

use App\Utils\Legacy\AntigateUtil;
use App\Utils\Legacy\CaptchaUtil;
use App\Utils\Legacy\CookieUtil;
use App\Utils\Legacy\CookieUtilStatic;
use App\Utils\Legacy\ExceptionUtilStatic;
use App\Utils\Legacy\LoggerUtil;
use App\Utils\Legacy\NeuroUtil;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Swoole\Coroutine;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Response;

#[AsCommand(name: 'app:update-sessions')]
class UpdateSessionsCommand extends Command implements LoggerAwareInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    use LoggerAwareTrait;

    public const NAME = 'app:update-sessions';

    private const DEFAULT_CAPTCHA_TIMEOUT = 20;
    private const DEFAULT_HTTP_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:106.0) Gecko/20100101 Firefox/106.0';
    private const DEFAULT_HTTP_TIMEOUT = 5;
    private const DEFAULT_IDLE_TIME = 1;
    private const DEFAULT_MAX_SECONDS = 20;
    private const DEFAULT_MAX_SESSIONS = 20;

    public function __construct(
        private readonly AntigateUtil $antigateUtil,
        private readonly CaptchaUtil $captchaUtil,
        private readonly Client $client,
        private readonly Connection $connection,
        private readonly CookieUtil $cookieUtil,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerUtil $loggerUtil,
        private readonly NeuroUtil $neuroUtil,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('number', InputArgument::OPTIONAL, default: 0)
            ->addOption('captcha-timeout', mode: InputOption::VALUE_REQUIRED, default: self::DEFAULT_CAPTCHA_TIMEOUT)
            ->addOption('http-timeout', mode: InputOption::VALUE_REQUIRED, default: self::DEFAULT_HTTP_TIMEOUT)
            ->addOption('http-agent', mode: InputOption::VALUE_REQUIRED, default: self::DEFAULT_HTTP_AGENT)
            ->addOption('idle-time', mode: InputOption::VALUE_REQUIRED, default: self::DEFAULT_IDLE_TIME)
            ->addOption('max-seconds', mode: InputOption::VALUE_REQUIRED, default: self::DEFAULT_MAX_SECONDS)
            ->addOption('max-sessions', mode: InputOption::VALUE_REQUIRED, default: self::DEFAULT_MAX_SESSIONS)
            ->addOption('timeless', mode: InputOption::VALUE_NONE)
            ->addOption('exclude', mode: InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED)
            ->addOption('include', mode: InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $exclude = $input->getOption('exclude');
        $include = $input->getOption('include');

        $number = (int) $input->getArgument('number');

        $idle_time = $input->getOption('idle-time');

        $daemonnum = $number;
        $proxyfilter = ''; // " AND mod(id, 10) + 1= $daemonnum";
        $sourceaccessfilter = ''; // " AND mod(sourceaccessid,10)+1=0";
        $sessionfilter = ''; // " AND (proxyid IS NULL OR mod(session.proxyid, 10) + 1 = $daemonnum)";

        // Проверка на повторный запуск
        $flock = \fopen("/var/run/app.update-sessions.$number.lock", 'w');

        if (!($flock && \flock($flock, \LOCK_EX | \LOCK_NB))) {
            $io->error('Deamon already started');

            return self::FAILURE;
        }

        $daemonstarttime = \microtime(true);

        $this->logger->info('Daemon started');

        $sessions = [];
        $wait = true;

        $this->cleanNewSessionsWithFreezedCaptcha();
        $this->resetUpdatableSessionsWithFreezedCaptcha();
        $this->deleteUsedSessionsOverAnHour();
        $this->cleanNewSessionsWithFreezedCaptcha();
        $this->detachOldestSessionsOverExecutedRequests();
        $this->deleteExpiredCaptcha($idle_time);
        $this->unlockLockedLogins();

        while ($wait) {
            $this->process(
                (int) $input->getOption('captcha-timeout'),
                $input->getOption('http-agent'),
                (int) $input->getOption('http-timeout'),
                $idle_time,
                (int) $input->getOption('max-seconds'),
                (int) $input->getOption('max-sessions'),
                $input->getOption('timeless'),
                $this->container->getParameter('app.antigate.host'),
                $this->container->getParameter('app.antigate.host2'),
                $this->container->getParameter('app.antigate.key'),
                $this->container->getParameter('app.antigate.key2'),
                $this->container->getParameter('app.capcha.host'),
                $this->container->getParameter('app.capcha.host2'),
                $this->container->getParameter('app.capcha.key'),
                $this->container->getParameter('app.capcha.key2'),
                $this->container->getParameter('app.captcha_v3.host'),
                $this->container->getParameter('app.captcha_v3.host2'),
                $this->container->getParameter('app.captcha_v3.key'),
                $this->container->getParameter('app.captcha_v3.key2'),
                $this->container->getParameter('app.h_captcha.host'),
                $this->container->getParameter('app.h_captcha.host2'),
                $this->container->getParameter('app.h_captcha.key'),
                $this->container->getParameter('app.h_captcha.key2'),
                $proxyfilter,
                $sourceaccessfilter,
                $sessionfilter,
                $daemonstarttime,
                $exclude,
                $include,
                $sessions,
                $wait,
            );
        }

        /*
        // Активируем приостановленные прокси (условие приостановления уже не выполняется)
        $sql = <<<SQL
        UPDATE proxy SET status=1
        WHERE enabled>0 AND status=0 AND id NOT IN (
        SELECT proxyid FROM proxyhourstats WHERE successrate<0.3)
        AND unix_timestamp(now())-unix_timestamp(lasttime)>600
        SQL;
        db_execute($db,$sql);
        // Деактивируем нерабочие прокси (успешность <50% за последний час)
        $sql = <<<SQL
        UPDATE proxy SET status=0
        WHERE enabled>0 AND status>0 AND (id IN (
        SELECT proxyid FROM proxyhourstats WHERE successrate<0.3)
        OR (unix_timestamp(now())-unix_timestamp(lasttime)<=600
        AND unix_timestamp(now())-unix_timestamp(successtime)>600)
        )
        SQL;
        db_execute($db,$sql);
        // Выключаем мертвые прокси (успешность <30% за сутки)
        $sql = <<<SQL
        UPDATE proxy SET enabled=0,status=0,endtime=now()
        WHERE enabled>0 AND in IN (
        SELECT proxyid FROM proxystats WHERE successrate<0.3)
        SQL;
        db_execute($db,$sql);
        */
        // Отключаемся от базы данных

        $this->logger->info('Daemon stopped');
        $this->loggerUtil->log('CYCLE.txt', (int) $this->loggerUtil->load('CYCLE.txt') + 1);

        return self::SUCCESS;
    }

    private function cleanNewSessionsWithFreezedCaptcha(): void
    {
        $this->logger->debug('Удаляем новые сессии с зависшими капчами');

        $this->connection->executeStatement(
            <<<'SQL'
delete
from session
where sessionstatusid = 1
  AND statuscode <> 'renew'
  AND starttime < SUBDATE(NOW(), INTERVAL 5 MINUTE)
  AND sourceid IN (
    SELECT id
    FROM source
    WHERE enabled = 1
  )
SQL,
        );
    }

    private function resetUpdatableSessionsWithFreezedCaptcha(): void
    {
        $this->logger->debug('Сбрасываем обновляемые сессии с зависшими капчами');

        $this->connection->executeStatement(
            <<<'SQL'
update session
set sessionstatusid = 7,
    lasttime = now(),
    statuscode = 'captchaerror'
where sessionstatusid = 1
  AND statuscode = 'renew'
  AND lasttime < SUBDATE(NOW(), INTERVAL 5 MINUTE)
SQL,
        );

        $this->connection->executeStatement(
            <<<'SQL'
update session
set sessionstatusid = 7,
    lasttime = now(),
    statuscode = 'captchaerror'
where sessionstatusid = 4
  AND captchaimage > ''
  AND lasttime < SUBDATE(NOW(), INTERVAL 5 MINUTE)
SQL,
        );
    }

    private function deleteUsedSessionsOverAnHour(): void
    {
        $this->logger->debug('Удаляем использованные сессии старше 1 часа');

        $this->connection->executeStatement(
            <<<'SQL'
delete
from session
where sessionstatusid IN (3, 4, 5)
  AND endtime IS NOT NULL
  AND (
    endtime < SUBDATE(NOW(), INTERVAL 1 HOUR)
    OR captcha_service IS NULL
  )
SQL,
        );
    }

    private function cleanProxyStatsOverAnHour(): void
    {
        $this->logger->debug('Очищаем статистику прокси старше 1 дня');

        $this->connection->executeStatement(
            <<<'SQL'
delete
from proxyusage
where lasttime < SUBDATE(NOW(), INTERVAL 1 DAY)
SQL,
        );
    }

    private function detachOldestSessionsOverExecutedRequests(): void
    {
        $this->logger->debug('Отвязываем сессии от старых и выполненных запросов');

        $this->connection->executeStatement(
            <<<'SQL'
update session
set request_id = NULL
where request_id IS NOT NULL
  AND (
    SELECT COUNT(*)
    FROM RequestNew
    WHERE id = session.request_id
      AND (
        status = 1
        OR unix_timestamp(now()) - unix_timestamp(created_at) > 600
      )
  )
SQL,
        );
    }

    private function deleteExpiredCaptcha(int $idle_time): void
    {
        $this->logger->debug('Удаляем капчи с истекшим сроком');

        $this->connection->executeStatement(
            <<<SQL
UPDATE session
SET captcha='',
    statuscode='captchaexpired'
WHERE sessionstatusid IN (2, 6, 7)
  and captcha >''
  AND sourceid IN (
    SELECT id
    from source
    WHERE captcha_time > 0
  )
  AND unix_timestamp(now()) - unix_timestamp(captchatime) + {$idle_time} + 1 >= (
    SELECT captcha_time
    from source
    WHERE id = session.sourceid
  )
SQL
        );
    }

    private function unlockLockedLogins(): void
    {
        $this->logger->debug('Разблокируем заблокированные логины');

        $this->connection->executeStatement(
            <<<SQL
UPDATE sourceaccess
SET unlocktime = null
WHERE unlocktime IS NOT NULL
  AND unlocktime < now()
SQL,
        );
    }

    private function process(
        int $captcha_timeout,
        string $http_agent,
        int $http_timeout,
        int $idle_time,
        int $max_seconds,
        int $max_sessions,
        bool $timeless,
        string $antigate_host,
        string $antigate_host2,
        string $antigate_key,
        string $antigate_key2,
        string $captcha_host,
        string $captcha_host2,
        string $captcha_key,
        string $captcha_key2,
        string $captchav3_host,
        string $captchav3_host2,
        string $captchav3_key,
        string $captchav3_key2,
        string $hcaptcha_host,
        string $hcaptcha_host2,
        string $hcaptcha_key,
        string $hcaptcha_key2,
        string $proxyfilter,
        string $sourceaccessfilter,
        string $sessionfilter,
        float $daemonstarttime,
        array $exclude,
        array $include,
        array $sessions,
        bool &$wait,
    ): void {
        $starttime = \microtime(true);

        $this->unlockLockedSessions();
        $this->completeExpiredSessions($idle_time);
        $this->cleanExpiredRecaptchaTokens();
        $this->completeExpiredSessions($idle_time);

        $this->renewSessionsBecomeInactive(
            $starttime,
            $daemonstarttime,
            $http_agent,
            $http_timeout,
            $max_seconds,
            $timeless,
            $sessionfilter,
        );

        $this->cancelUnnecessarySessions($sessionfilter);

        $this->updateNewCaptchaOnRefreshedSessions(
            $starttime,
            $daemonstarttime,
            $http_timeout,
            $max_seconds,
            $http_agent,
            $antigate_host,
            $antigate_host2,
            $antigate_key,
            $antigate_key2,
            $captcha_host,
            $captcha_host2,
            $captcha_key,
            $captcha_key2,
            $captchav3_host,
            $captchav3_host2,
            $captchav3_key,
            $captchav3_key2,
            $hcaptcha_host,
            $hcaptcha_host2,
            $hcaptcha_key,
            $hcaptcha_key2,
            $timeless,
            $sessionfilter,
            $sessions,
        );

        $resultCount = $this->readSourceListAndCountOfNeedsSessions(
            $daemonstarttime,
            $http_agent,
            $http_timeout,
            $max_seconds,
            $max_sessions,
            $timeless,
            $antigate_host,
            $antigate_host2,
            $antigate_key,
            $antigate_key2,
            $captcha_host,
            $captcha_host2,
            $captcha_key,
            $captcha_key2,
            $captchav3_host,
            $captchav3_key,
            $hcaptcha_host,
            $hcaptcha_host2,
            $hcaptcha_key,
            $hcaptcha_key2,
            $proxyfilter,
            $sourceaccessfilter,
            $exclude,
            $include,
            $sessions,
        );

        //                dd($sessions);

        if (\count($sessions) > 0) {
            foreach ($sessions as $sessionid => &$s) {
                if (!isset($s['lasttime'])) {
                    $s['lasttime'] = $s['starttime'];
                }
                $code = $s['code'];
                $lasttime = $s['lasttime'];
                if ('neuro' == $s['antigatehost']) {
                    $captcha_value = $s['antigateid'];
                } elseif (
                    (\microtime(true) - $s['starttime'] < 10) || (
                        \microtime(
                            true
                        ) - $s['lasttime'] < 5)
                ) { // еще рано проверять
                    $captcha_value = false;
                } elseif (\microtime(true) - $s['starttime'] > $captcha_timeout) { // очень долго распознается
                    //                $this->logger->info("Captcha id {$s['antigateid']} ({$s['antigatehost']}) solving timeout for session: $sessionid (".$sessions[$sessionid]['code'].")");
                    $captcha_value = 'ERROR_TIMEOUT_EXCEEDED';
                } else {
                    if ('image' == $s['captcha_format']) {
                        $captcha_value = \trim(
                            $this->antigateUtil->antigate_get(
                                $s['antigateid'],
                                $s['antigatekey'],
                                false,
                                $s['antigatehost']
                            )
                        );
                    } // Запрашиваем значение капчи
                    else {
                        $captcha_value = \trim(
                            $this->captchaUtil->captcha_result(
                                $s['antigateid'],
                                $s['antigatekey'],
                                false,
                                $s['antigatehost']
                            )
                        );
                    } // Запрашиваем значение токена
                    $s['lasttime'] = \microtime(true);
                }
                if ($captcha_value && (!\str_contains($captcha_value, 'ERROR'))) {
                    if ('image' == $s['captcha_format'] && 3 == $s['captcha_type']) {
                        $captcha_value = \trim(
                            \strtr(
                                $captcha_value,
                                [
                                    'A' => 'А',
                                    'B' => 'В',
                                    'C' => 'С',
                                    'E' => 'Е',
                                    'F' => 'Г',
                                    'H' => 'Н',
                                    'K' => 'К',
                                    'M' => 'М',
                                    'N' => 'И',
                                    'O' => 'О',
                                    'P' => 'Р',
                                    'R' => 'Я',
                                    'T' => 'Т',
                                    'Y' => 'У',
                                    'X' => 'Х',
                                    'a' => 'а',
                                    'c' => 'с',
                                    'e' => 'е',
                                    'f' => 'г',
                                    'h' => 'н',
                                    'k' => 'к',
                                    'm' => 'м',
                                    'n' => 'п',
                                    'o' => 'о',
                                    'p' => 'р',
                                    'r' => 'г',
                                    't' => 'т',
                                    'y' => 'у',
                                    'x' => 'х',
                                ]
                            )
                        );
                    }
                    $this->connection->executeStatement(
                        "UPDATE session SET captchaimage=NULL,captcha_reporttime=NULL,captchatime=now(),captcha='$captcha_value'".('image' == $s['captcha_type'] ? '' : ',lasttime=now(),endtime=null,sessionstatusid=1 ')." WHERE id=$sessionid"
                    );
                    $this->connection->executeStatement(
                        "UPDATE session SET captchaimage=NULL,captcha_reporttime=NULL,captchatime=now(),captcha='$captcha_value'".('image' == $s['captcha_type'] ? '' : ',lasttime=now(),endtime=null,sessionstatusid=1 ')." WHERE id=$sessionid"
                    );
                    $this->logger->info(
                        "Recognized captcha id {$s['antigateid']} ({$s['antigatehost']}) for session: $sessionid - ".(
                            \strlen(
                                $captcha_value
                            ) < 20 ? $captcha_value : \substr($captcha_value, 0, 2).'...'.\substr(
                                $captcha_value,
                                \strlen($captcha_value) - 5
                            )
                        )." ($code), starttime = ".\date(
                            'H:i:s',
                            (int) $s['starttime']
                        ).', lasttime = '.\date(
                            'H:i:s',
                            (int) $lasttime
                        ).''
                    );
                    if ('image' == $s['captcha_format']) {
                        $captcha_pic = "captcha/$code/__$sessionid.jpg";
                        $captcha_pic_new = "captcha/$code/$captcha_value.jpg";
                        $this->loggerUtil->rename($captcha_pic, $captcha_pic_new);
                    }

                    if (
                        ('image' == $s['captcha_format']) && $s['captcha_size'] && (
                            \mb_strlen(
                                $captcha_value
                            ) != $s['captcha_size'])
                    ) {
                        $captcha_value = 'ERROR_INVALID_SIZE';
                        $this->logger->info('Invalid captcha size');
                    } elseif ($s['method'] && $s['url']) {
                        $proxy = $s['proxy'];
                        $proxy_auth = $s['proxy_auth'];
                        $s['params'][$s['field']] = $captcha_value;
                        if ($s['token_field']) {
                            $s['params'][$s['token_field']] = $s['token'];
                        }
                        $data = \http_build_query($s['params']);
                        $check_url = $s['url'];
                        $check_options = [
                            'http' => [
                                'method' => $s['method'],
                                'timeout' => $http_timeout,
                                'ignore_errors' => true,
                                'header' => "Cache-Control: no-cache, no-store, must-revalidate\r\n".
                                    "User-Agent: $useragent\r\n".
                                    //                            "Referer: $form_url\r\n" .
                                    CookieUtilStatic::cookies_header($s['cookies']),
                            ],
                            'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                            ],
                        ];
                        if ('POST' == $s['method']) {
                            $check_options['http']['content'] = $data;
                            $check_options['http']['header'] = "Content-Type: application/x-www-form-urlencoded\r\n".'Content-Length: '.\strlen(
                                $data
                            )."\r\n".$check_options['http']['header'];
                        } else {
                            $check_url .= (\strpos($check_url, '?') ? '&' : '?').\http_build_query($s['params']);
                            $check_options['http']['header'] = "Content-Length: 0\r\nX-Requested-With: XMLHttpRequest\r\n".$check_options['http']['header'];
                        }
                        if ($proxy) {
                            $check_options['http']['proxy'] = $proxy;
                            $check_options['http']['request_fulluri'] = true;
                            if ($proxy_auth) {
                                $check_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                            }
                            $this->logger->info("[8] Using proxy $proxy ($code)");
                        }
                        $check_context = \stream_context_create($check_options);
                        $check = \file_get_contents($check_url, false, $check_context);
                        $this->logger->info("Checking captcha: $check_url, size: ".\strlen($check));
                        $this->loggerUtil->log(
                            "logs/$code/check_$sessionid.htm",
                            (isset($http_response_header) ? \implode(
                                "\n",
                                $http_response_header
                            ) : '')."\n\n".$check
                        );
                        $cookies = \array_merge(
                            $s['cookies'],
                            CookieUtilStatic::parse_cookies($http_response_header)
                        );
                        $cookies_str = \addslashes(CookieUtilStatic::cookies_str($cookies));

                        if ($s['token_regexp']) {
                            if (\preg_match($s['token_regexp'], $check, $matches)) {
                                $token = $matches[1];
                                //                            $this->logger->info("Check content: $check ($code)");
                                //                            $this->logger->info("Check regexp: {$s['token_regexp']} ($code)");
                                $this->logger->info("Session token: $token ($code)");
                                $this->connection->executeStatement(
                                    "UPDATE session SET cookies='$cookies_str', token='$token' WHERE id=$sessionid"
                                );
                            } elseif (!$check) {
                                //                            $captcha_value = false;
                                $captcha_value = 'ERROR_CHECKING_CAPTCHA';
                                $this->logger->info("Checking captcha failed ($code)");
                            } else {
                                $captcha_value = 'ERROR_INVALID_CAPTCHA';
                                $this->logger->info('Session token '.$s['token_regexp']." not found ($code)");
                                //                            $this->logger->info($check);
                            }
                        }
                    }
                } elseif ($captcha_value) {
                    $this->connection->executeStatement(
                        "UPDATE session SET captcha='$captcha_value' WHERE id=$sessionid"
                    );
                    $this->logger->info(
                        "Captcha id {$s['antigateid']} ({$s['antigatehost']}) solving error $captcha_value for session: $sessionid ($code), starttime = ".\date(
                            'H:i:s',
                            $s['starttime']
                        ).', lasttime = '.\date('H:i:s', $lasttime).''
                    );
                }

                if ($captcha_value) {
                    if (!\str_contains($captcha_value, 'ERROR')) {
                        $this->connection->executeStatement(
                            "UPDATE session SET sessionstatusid=2,lasttime=now() WHERE id=$sessionid AND sessionstatusid=1"
                        );
                    } elseif ('ERROR_CHECKING_CAPTCHA' == $captcha_value) {
                        $this->connection->executeStatement(
                            "UPDATE session SET sessionstatusid=7,lasttime=now(),captcha_service=NULL,captcha_id=NULL WHERE id=$sessionid AND sessionstatusid=1 AND statuscode='renew'"
                        );
                        $this->connection->executeStatement(
                            "UPDATE session SET captchaimage=NULL,captchatime=now(),captcha='',sessionstatusid=4,statuscode='checkingerror',endtime=now() WHERE id=$sessionid AND sessionstatusid=1"
                        );
                    } else {
                        $this->connection->executeStatement(
                            "UPDATE session SET sessionstatusid=7,lasttime=now(),captcha_service=NULL,captcha_id=NULL,captcha='' WHERE id=$sessionid AND sessionstatusid=1 AND statuscode='renew'"
                        );
                        $this->connection->executeStatement(
                            "UPDATE session SET captchaimage=NULL,captchatime=now(),sessionstatusid=4,statuscode='invalidcaptcha',endtime=now() WHERE id=$sessionid AND sessionstatusid=1"
                        );
                    }
                    unset($sessions[$sessionid]);
                } else {
                    //                $this->logger->info("Captcha id {$s['antigateid']} ({$s['antigatehost']}) not ready for session: $sessionid ($code), starttime = ".date("H:i:s",$s['starttime']).", lasttime = ".date("H:i:s",$lasttime)."");
                }
            }
        }

        // Если новых сессий не нужlно, можно подождать
        if (0 === $resultCount) {
            \sleep($idle_time);
        }

        // Отправляем отчеты о неверно распознанных капчах
        $sql = <<<SQL
SELECT session.*,source.code source_code FROM session,source WHERE session.sourceid=source.id AND session.sessionstatusid=4 AND session.captcha_service IS NOT NULL AND session.captcha_reporttime IS NULL
$sessionfilter
ORDER BY session.captchatime DESC LIMIT 100
SQL;
        $result = $this->connection->executeQuery($sql);
        while ($row = $result->fetchAssociative()) {
            //        $this->logger->info("Reporting captcha ".$row['captcha']." ID=".$row['captcha_id']." to ".$row['captcha_service']);
            if ('ERROR_' == \substr($row['captcha'], 0, 6) || !$row['captcha_id']) {
            } elseif ('rucaptcha.com' == $row['captcha_service']) {
                $report_result = \trim(
                    $this->antigateUtil->antigate_reportbad(
                        $row['captcha_id'],
                        'rucaptcha.com' == $antigate_host ? $antigate_key : $antigate_key2,
                        false,
                        'rucaptcha.com'
                    )
                );
                $this->logger->info(
                    'Captcha '.(\strlen($row['captcha']) < 20 ? $row['captcha'] : \substr(
                        $row['captcha'],
                        0,
                        2
                    ).'...'.\substr(
                        $row['captcha'],
                        \strlen($row['captcha']) - 5
                    )
                    )." ({$row['source_code']}) ID={$row['captcha_id']} from {$row['captcha_service']} reported as bad. Result is $report_result"
                );
            } elseif ('anti-captcha.com' == $row['captcha_service'] || 'api.anti-captcha.com' == $row['captcha_service']) {
                if (\strlen($row['captcha']) < 20) {
                    $report_result = \trim(
                        $this->captchaUtil->captcha_bad(
                            $row['captcha_id'],
                            'anti-captcha.com' == $antigate_host ? $antigate_key : $antigate_key2,
                            false,
                            'api.anti-captcha.com'
                        )
                    );
                } else {
                    $report_result = \trim(
                        $this->captchaUtil->recaptcha_bad(
                            $row['captcha_id'],
                            'anti-captcha.com' == $antigate_host ? $antigate_key : $antigate_key2,
                            false,
                            'api.anti-captcha.com'
                        )
                    );
                }
                $this->logger->info(
                    'Captcha '.(\strlen($row['captcha']) < 20 ? $row['captcha'] : \substr(
                        $row['captcha'],
                        0,
                        2
                    ).'...'.\substr(
                        $row['captcha'],
                        \strlen($row['captcha']) - 5
                    )
                    )." ({$row['source_code']}) ID={$row['captcha_id']} from {$row['captcha_service']} reported as bad. Result is $report_result"
                );
            } else {
                $this->logger->info(
                    'Captcha '.(\strlen($row['captcha']) < 20 ? $row['captcha'] : \substr(
                        $row['captcha'],
                        0,
                        2
                    ).'...'.\substr(
                        $row['captcha'],
                        \strlen($row['captcha']) - 5
                    )
                    )." ({$row['source_code']}) ID={$row['captcha_id']} from {$row['captcha_service']} was bad."
                );
            }
            $this->connection->executeStatement(
                'UPDATE session SET captcha_reporttime=now() WHERE id='.$row['id']
            );
            $this->connection->executeStatement(
                'UPDATE session SET captcha_reporttime=now() WHERE id='.$row['id']
            );
            $this->connection->executeStatement(
                "UPDATE session SET used=0,success=0,captchaimage=NULL,captcha='',sessionstatusid=6,statuscode='toomanyinvalid',unlocktime=date_add(now(),interval 24 hour) WHERE captchaimage IS NOT NULL AND used/(success+1)>5 AND id=".$row['id']
            );
            $this->connection->executeStatement(
                "UPDATE session SET used=0,success=0,captchaimage=NULL,captcha='',sessionstatusid=6,statuscode='toomanyinvalid',unlocktime=date_add(now(),interval 24 hour) WHERE captchaimage IS NOT NULL AND used/(success+1)>5 AND id=".$row['id']
            );
            $this->connection->executeStatement(
                "UPDATE session SET sessionstatusid=7,lasttime=now(),statuscode='captcha',captcha='' WHERE captcha_reporttime IS NOT NULL AND captchaimage IS NOT NULL AND id=".$row['id']
            );
            $this->connection->executeStatement(
                "UPDATE session SET sessionstatusid=7,lasttime=now(),statuscode='captcha',captcha='' WHERE captcha_reporttime IS NOT NULL AND captchaimage IS NOT NULL AND id=".$row['id']
            );
            if (\strlen($row['captcha']) < 20) {
                $captcha_dir = "captcha/{$row['source_code']}";
                $captcha_pic = "$captcha_dir/{$row['captcha']}.jpg";
                $captcha_new_dir = "$captcha_dir/bad.{$row['captcha_service']}";
                $captcha_new_pic = "$captcha_new_dir/{$row['captcha']}.jpg";
                $this->loggerUtil->rename($captcha_pic, $captcha_new_pic);
            }
        }

        // Отправляем отчеты об успешно распознанных капчах
        $sql = <<<SQL
SELECT session.*,source.code source_code FROM session,source WHERE session.sourceid=source.id AND session.sessionstatusid IN (2,3,7) AND (session.statuscode='success' OR (source.captcha_check_method>'' AND source.captcha_check_token_regexp>'')) AND session.captcha_service IS NOT NULL AND session.captcha_reporttime IS NULL
$sessionfilter
ORDER BY session.captchatime DESC LIMIT 100
SQL;
        $result = $this->connection->executeQuery($sql);
        while ($row = $result->fetchAssociative()) {
            //        $this->logger->info("Reporting captcha ".$row['captcha']." ID=".$row['captcha_id']." to ".$row['captcha_service']);
            if (!$row['captcha_id']) {
            } elseif ('rucaptcha.com' == $row['captcha_service']) {
                $report_result = \trim(
                    $this->antigateUtil->antigate_reportgood(
                        $row['captcha_id'],
                        'rucaptcha.com' == $antigate_host ? $antigate_key : $antigate_key2,
                        false,
                        'rucaptcha.com'
                    )
                );
                $this->logger->info(
                    'Captcha '.(\strlen($row['captcha']) < 20 ? $row['captcha'] : \substr(
                        $row['captcha'],
                        0,
                        2
                    ).'...'.\substr(
                        $row['captcha'],
                        \strlen($row['captcha']) - 5
                    )
                    )." ({$row['source_code']}) ID={$row['captcha_id']} from {$row['captcha_service']} reported as good. Result is $report_result"
                );
            } elseif ('anti-captcha.com' == $row['captcha_service'] || 'api.anti-captcha.com' == $row['captcha_service']) {
                if (\strlen($row['captcha']) < 20) {
                    $report_result = 'NONE'; // trim(captcha_good($row['captcha_id'],$antigate_host=='anti-captcha.com'?$antigate_key:$antigate_key2,false,'api.anti-captcha.com'));
                } else {
                    $report_result = \trim(
                        $this->captchaUtil->recaptcha_good(
                            $row['captcha_id'],
                            'anti-captcha.com' == $antigate_host ? $antigate_key : $antigate_key2,
                            false,
                            'api.anti-captcha.com'
                        )
                    );
                }
                $this->logger->info(
                    'Captcha '.(\strlen($row['captcha']) < 20 ? $row['captcha'] : \substr(
                        $row['captcha'],
                        0,
                        2
                    ).'...'.\substr(
                        $row['captcha'],
                        \strlen($row['captcha']) - 5
                    )
                    )." ({$row['source_code']}) ID={$row['captcha_id']} from {$row['captcha_service']} reported as good. Result is $report_result"
                );
            } else {
                $this->logger->info(
                    'Captcha '.(\strlen($row['captcha']) < 20 ? $row['captcha'] : \substr(
                        $row['captcha'],
                        0,
                        2
                    ).'...'.\substr(
                        $row['captcha'],
                        \strlen($row['captcha']) - 5
                    )
                    )." ({$row['source_code']}) ID={$row['captcha_id']} from {$row['captcha_service']} was good."
                );
            }
            $this->connection->executeStatement(
                "UPDATE session SET captcha='',captcha_reporttime=now() WHERE id=".$row['id']
            );
            $this->connection->executeStatement(
                "UPDATE session SET captcha='',captcha_reporttime=now() WHERE id=".$row['id']
            );
            $this->connection->executeStatement(
                "UPDATE session SET used=0,success=0,sessionstatusid=7,lasttime=now(),statuscode='captcha' WHERE captcha_reporttime IS NOT NULL AND captchaimage IS NOT NULL AND id=".$row['id']
            );
            $this->connection->executeStatement(
                "UPDATE session SET used=0,success=0,sessionstatusid=7,lasttime=now(),statuscode='captcha' WHERE captcha_reporttime IS NOT NULL AND captchaimage IS NOT NULL AND id=".$row['id']
            );
            if (\strlen($row['captcha']) < 20) {
                $captcha_dir = "captcha/{$row['source_code']}";
                $captcha_pic = "$captcha_dir/{$row['captcha']}.jpg";
                $captcha_new_dir = "$captcha_dir/good.{$row['captcha_service']}";
                $captcha_new_pic = "$captcha_new_dir/{$row['captcha']}.jpg";
                $this->loggerUtil->rename($captcha_pic, $captcha_new_pic);
            }
        }

        $wait = (\count($sessions) > 0);
    }

    private function unlockLockedSessions(): void
    {
        $this->logger->debug('Разблокируем заблокированные сессии');

        $this->connection->executeStatement(
            <<<SQL
UPDATE session
SET sessionstatusid = 2,
    statuscode = 'unlocked'
WHERE sessionstatusid = 6
  AND unlocktime < now()
SQL,
        );
    }

    private function completeExpiredSessions(int $idle_time): void
    {
        $this->logger->debug('Завершаем просроченные сессии');

        $this->connection->executeStatement(
            <<<SQL
UPDATE session
SET sessionstatusid = 5,
    statuscode = 'ended',
    endtime = now()
WHERE sessionstatusid IN (2, 6, 7)
  AND sourceid IN (
    SELECT id
    from source
    WHERE session_time > 0
  )
  AND unix_timestamp(now()) - unix_timestamp(starttime) + {$idle_time} + 1 >= (
    SELECT session_time
    from source
    WHERE id = session.sourceid
  )
SQL,
        );
    }

    private function cleanExpiredRecaptchaTokens(): void
    {
        $this->logger->info('Стираем просроченные токены рекапчи');

        $this->connection->executeStatement(
            <<<SQL
UPDATE session
SET sessionstatusid = 7,
    lasttime = now(),
    statuscode = 'captchaexpired',
    captcha = '',
    captcha_id = NULL
WHERE sessionstatusid IN (2, 6)
  AND captcha > ''
  AND sourceid IN (
    SELECT id
    from source
    WHERE captcha_format IN ('recaptcha', 'v3', 'hcaptcha')
      AND captcha_check_method = ''
  )
  AND unix_timestamp(now()) - unix_timestamp(captchatime) > 110
SQL,
        );
    }

    private function completeInactiveSessions(int $idle_time): void
    {
        $this->logger->info('Завершаем неактивные сессии');

        $this->connection->executeStatement(
            <<<SQL
UPDATE session
SET sessionstatusid = 5,
    statuscode = 'inactive',
    endtime = now()
WHERE sessionstatusid IN (2, 6, 7)
  AND sourceid IN (
    SELECT id
    from source
    WHERE session_inactivity > 0
  )
  AND unix_timestamp(now()) - unix_timestamp(lasttime) + {$idle_time} + 1 >= (
    SELECT session_inactivity
    from source
    WHERE id = session.sourceid
  )
SQL,
        );
    }

    private function renewSessionsBecomeInactive(
        float $starttime,
        float $daemonstarttime,
        string $http_agent,
        int $http_timeout,
        int $max_seconds,
        bool $timeless,
        string $sessionfilter,
    ): void {
        $this->logger->debug('Продлеваем сессии, которые скоро станут неактивными');

        $result = $this->connection->executeQuery(
            <<<SQL
SELECT session.*,
       source.code,
       source.name,
       source.url,
       source.ping_path,
       source.ping_token,
       source.ping_method,
       source.ping_header,
       source.ping_content,
       source.ping_regexp,
       source.form_path,
       source.form_token,
       source.form_header,
       source.form_regexp,
       source.logoff_path,
       source.useragent,
       source.codepage
FROM session,
     source
WHERE session.sourceid = source.id
  AND session.sessionstatusid IN (2, 6, 7)
  AND source.session_inactivity > 0
  AND unix_timestamp(now()) - unix_timestamp(session.lasttime) + 60 >= source.session_inactivity / 2

UNION

SELECT session.*,
       source.code,
       source.name,
       source.url,
       source.ping2_path ping_path,
       '' ping_token,
       source.ping2_method ping_method,
       '' ping_header,
       '' ping_content,
       '' ping_regexp,
       '' form_path,
       '' form_token,
       '' form_header,
       '' form_regexp,
       source.logoff_path,
       source.useragent,
       source.codepage
FROM session,
     source
WHERE session.sourceid = source.id
  AND session.sessionstatusid IN (2, 6, 7)
  AND source.session_inactivity > 0
  AND source.ping2_path <> ''
  AND unix_timestamp(now()) - unix_timestamp(session.lasttime) + 60 >= source.session_inactivity / 2
  {$sessionfilter}
SQL,
        );

        while (($row = $result->fetchAssociative()) && ($timeless || (\microtime(true) - $daemonstarttime < 300))) {
            $sessionid = $row['id'];
            $sourceid = $row['sourceid'];
            $code = $row['code'];
            $name = $row['name'];
            $useragent = '' == $row['useragent'] ? $http_agent : $row['useragent'];
            $codepage = $row['codepage'];
            $cookies = CookieUtilStatic::str_cookies($row['cookies']);
            $url = $row['url'];
            $logoff_path = $row['logoff_path'];

            if ($row['ping_path']) {
                $ping_url = ('http' == \substr($row['ping_path'], 0, 4)
                        ? ''
                        : ($row['server'] ?: $url)).$row['ping_path'];

                if ($row['ping_token']) {
                    $ping_url .= (\strpos($ping_url, '?') ? '&' : '?').$row['ping_token']
                        .'='.\urlencode($row['token']);
                }

                $ping_regexp = $row['ping_regexp'];
                $ping_method = $row['ping_method'];
                $ping_header = $row['ping_header'];
                $ping_content = $row['ping_content'];
            } else {
                $ping_url = ('http' == \substr($row['form_path'], 0, 4)
                        ? ''
                        : ($row['server'] ?: $url)).$row['form_path'];

                if ($row['form_token']) {
                    $ping_url .= (\strpos($ping_url, '?') ? '&' : '?').$row['form_token']
                        .'='.\urlencode($row['token']);
                }

                $ping_regexp = $row['form_regexp'];
                $ping_method = 'GET';
                $ping_header = $row['form_header'];
                $ping_content = '';
            }

            $get_options = [
                'http' => [
                    'method' => $ping_method,
                    'timeout' => $http_timeout,
                    'follow_location' => 0,
                    'header' => "User-Agent: $useragent\r\n".
                        //                "Authorization: Bearer ".$row['token']."\r\n" .
                        ($ping_header ? $ping_header."\r\n" : '').
                        CookieUtilStatic::cookies_header($cookies),
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    //            'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
                ],
            ];

            if ('POST' == $ping_method) {
                $get_options['http']['content'] = $ping_content;
                $get_options['http']['header'] .=
                    //                                "X-Requested-With: XMLHttpRequest\r\n" .
//                                "Content-Type: application/x-www-form-urlencoded\r\n" .
                    'Content-Length: '.\strlen($ping_content)."\r\n";
            }

            $get_context = \stream_context_create($get_options);
            $ping = \file_get_contents($ping_url, false, $get_context);

            $this->logger->info("Pinging $ping_url, size: ".\strlen($ping));

            $redirects = 0;
            $next_url = $ping_url;

            while (
                /* !$ping && */
                isset($http_response_header)
                && \count($http_response_header) > 0
                && (false != \strpos($http_response_header[0], '500')
                    || false != \strpos($http_response_header[0], '307')
                    || false != \strpos($http_response_header[0], '303')
                    || false != \strpos($http_response_header[0], '302')
                    || false != \strpos($http_response_header[0], '301')
                )
                && (++$redirects < 10)
                && (\microtime(true) - $starttime < $max_seconds)
            ) {
                $this->logger->info('Header: '.\implode("\n", $http_response_header));

                $cookies = \array_merge($cookies, CookieUtilStatic::parse_cookies($http_response_header));

                $this->logger->info('Cookies: '.CookieUtilStatic::cookies_header($cookies));

                foreach ($http_response_header as $line) {
                    if (\str_contains($line, 'Location:')) {
                        $next_path = \trim(\substr($line, 9));
                        $purl = \parse_url($ping_url);
                        $server = $purl['scheme'].'://'.$purl['host'];

                        if (\array_key_exists('port', $purl)) {
                            $server .= ':'.$purl['port'];
                        }

                        $next_url = ('http' == \substr($next_path, 0, 4) ? '' : $server).$next_path;

                        $this->logger->info("Ping redirect: $next_url ($code)");
                    }
                }

                $get_options['http']['method'] = 'GET';
                $get_options['http']['header'] =
                    "Cache-Control: max-age=0\r\n".
                    //                "Connection: keep-alive\r\n" .
                    "User-Agent: $useragent\r\n".
                    CookieUtilStatic::cookies_header($cookies);

                $get_context = \stream_context_create($get_options);

                $ping = \file_get_contents($next_url, false, $get_context);

                $this->logger->info("Getting $next_url, size: ".\strlen($ping));
            }

            if ($codepage) {
                $ping = \iconv($codepage, 'utf-8', $ping);
            }

            $this->loggerUtil->log(
                "logs/$code/ping_$sessionid.htm",
                (isset($http_response_header) ? \implode("\n", $http_response_header) : '')."\n\n".$ping
            );

            if (
                ('POST' == $ping_method || $ping)
                && (!$logoff_path || \strpos($ping, $logoff_path))
            ) {
                if (!$ping_regexp || \preg_match($ping_regexp, $ping)) {
                    $cookies = \array_merge($cookies, CookieUtilStatic::parse_cookies($http_response_header));
                    $cookies_str = \addslashes(CookieUtilStatic::cookies_str($cookies));

                    $this->logger->info("Prolongated session $sessionid ($code)");

                    $sql = <<<SQL
UPDATE session SET cookies='$cookies_str',lasttime=now()
WHERE id=$sessionid
SQL;
                    $this->connection->executeStatement($sql);
                } else {
                    $this->logger->info("Invalid session $sessionid - not found ($code)");

                    $sql = <<<SQL
UPDATE session SET sessionstatusid=5,statuscode='invalid',endtime=now()
WHERE id=$sessionid
SQL;

                    $this->connection->executeStatement($sql);
                }
            } elseif ($ping) {
                $this->logger->info(
                    "Invalid session $sessionid - ".($ping ? 'not logged' : 'empty')." ($code)\n"
                );

                $this->loggerUtil->log(
                    "logs/$code/notlogged_$sessionid.htm",
                    (isset($http_response_header) ? \implode("\n", $http_response_header) : '')."\n\n".$ping
                );

                if ($ping) {
                    $sql = <<<SQL
UPDATE session SET sessionstatusid=5,statuscode='notlogged',endtime=now()
WHERE id=$sessionid
SQL;
                    $this->connection->executeStatement($sql);
                }
            }
        }
    }

    private function cancelUnnecessarySessions(string $sessionfilter): void
    {
        $this->logger->debug('Отменяем ненужное обновление сессий');

        $this->connection->executeStatement(
            <<<SQL
UPDATE session
SET sessionstatusid = 2,
    statuscode = '',
    captchaimage = null
WHERE sessionstatusid IN (7)
  AND lasttime < date_add(now(), interval -150 second)
  {$sessionfilter}
ORDER BY session.lasttime
LIMIT 10
SQL,
        );
    }

    private function updateNewCaptchaOnRefreshedSessions(
        float $starttime,
        float $daemonstarttime,
        int $http_timeout,
        int $max_seconds,
        string $http_agent,
        string $antigate_host,
        string $antigate_host2,
        string $antigate_key,
        string $antigate_key2,
        string $captcha_host,
        string $captcha_host2,
        string $captcha_key,
        string $captcha_key2,
        string $captchav3_host,
        string $captchav3_host2,
        string $captchav3_key,
        string $captchav3_key2,
        string $hcaptcha_host,
        string $hcaptcha_host2,
        string $hcaptcha_key,
        string $hcaptcha_key2,
        bool $timeless,
        string $sessionfilter,
        array &$sessions,
    ): void {
        $this->logger->debug('Получаем новые капчи на обновляемых сессиях');

        $result = $this->connection->executeQuery(
            <<<SQL
SELECT session.*,
       source.code,
       source.name,
       source.url,
       source.useragent,
       source.codepage,
       source.form_path,
       source.captcha_path,
       source.captcha_path_regexp,
       source.captcha_token,
       source.captcha_token_regexp,
       source.captcha_action,
       source.captcha_minscore,
       source.captcha_format,
       source.captchatypeid,
       source.captcha_size,
       source.captcha_check_method,
       source.captcha_check_path,
       source.captcha_check_token_regexp,
       source.captcha_field,
       source.token_field
FROM session,
     source
WHERE session.sourceid = source.id
  AND session.sessionstatusid IN (7)
  AND session.sourceid NOT IN (
    SELECT sourceid
    FROM session
    WHERE sessionstatusid = 1
    GROUP BY 1
    HAVING count(*) > 250
  )
  AND (
    captcha_id IS NULL
    OR captcha_reporttime IS NOT NULL
  )
  {$sessionfilter}
ORDER BY session.lasttime
LIMIT 50
SQL,
        );

        while (($row = $result->fetchAssociative()) && ($timeless || (\microtime(true) - $daemonstarttime < 300))) {
            $sessionid = $row['id'];
            $cookies = CookieUtilStatic::str_cookies($row['cookies']);
            $sourceid = $row['sourceid'];
            $code = $row['code'];
            $name = $row['name'];

            $this->logger->info("Renewing session: $sessionid ($code)");

            $url = $row['url'];
            $form_path = $row['form_path'];
            $form_url = ('http' == \substr($form_path, 0, 4) ? '' : $url).$form_path;
            $captcha_image = $row['captchaimage'];
            $captcha_path = $row['captcha_path'];
            $captcha_path_regexp = $row['captcha_path_regexp'];
            $captcha_token = $row['captcha_token'];
            $captcha_token_regexp = $row['captcha_token_regexp'];
            $captcha_action = $row['captcha_action'];
            $captcha_minscore = $row['captcha_minscore'];
            $captcha_format = $row['captcha_format'];
            $captcha_type = $row['captchatypeid'];
            $captcha_size = $row['captcha_size'];
            $captcha_check_method = $row['captcha_check_method'];
            $captcha_check_path = $row['captcha_check_path'];
            $captcha_check_url = $captcha_check_path;

            if ($captcha_check_path) {
                $captcha_check_url = ('http' == \substr($captcha_check_path, 0, 4)
                        ? ''
                        : $url).$captcha_check_path;
            }

            $captcha_check_token_regexp = $row['captcha_check_token_regexp'];
            $captcha_field = $row['captcha_field'];
            $token_field = $row['token_field'];

            $captcha_url = $captcha_path;
            $captcha = $captcha_image ? \base64_decode($captcha_image) : false;
            $token = '';
            $params = [];

            $proxy = $row['proxyid'];
            $proxy_auth = false;

            if ($captcha || 'recaptcha' == $captcha_format || 'hcaptcha' == $captcha_format || 'v3' == $captcha_format) {
                if ($proxy) {
                    $sql = "SELECT * FROM proxy WHERE id=$proxy";
                    $proxy_result = $this->connection->executeQuery($sql);

                    if ($proxy_row = $proxy_result->fetchAssociative()) {
                        $proxyid = $proxy_row['id'];
                        $proxy = 'tcp://'.$proxy_row['server'].':'.$proxy_row['port'];

                        if ($proxy_row['login']) {
                            $proxy_auth = \base64_encode($proxy_row['login'].':'.$proxy_row['password']);
                        } else {
                            $proxy_auth = false;
                        }
                    } else {
                        $proxy = 0;

                        return;
                    }
                }

                while ($captcha_url && !$captcha && (\microtime(true) - $starttime < $max_seconds)) {
                    $captcha_url = ('http' == \substr($captcha_url, 0, 4) ? '' : $url).$captcha_url;
                    $get_options = [
                        'http' => [
                            'method' => 'GET',
                            'timeout' => $http_timeout,
                            'follow_location' => 0,
                            'header' => "Cache-Control: no-cache, no-store, must-revalidate\r\n".
                                //                        "Connection: keep-alive\r\n" .
                                "User-Agent: $http_agent\r\n".
                                "Referer: $form_url\r\n".
                                CookieUtilStatic::cookies_header($cookies),
                        ],
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                        ],
                    ];

                    if ($proxy) {
                        $get_options['http']['proxy'] = $proxy;
                        //                    $get_options['http']['request_fulluri'] = true;

                        if ($proxy_auth) {
                            $get_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                        }

                        $this->logger->info("[1] Using proxy $proxyid $proxy ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);
                    }

                    $get_context = \stream_context_create($get_options);

                    if ($captcha_token && $token) {
                        if (isset($cookies[$captcha_token])) {
                            $token = $cookies[$captcha_token];
                        }

                        $captcha_url = $captcha_url.'?'.$captcha_token.'='.$token;
                    }

                    $captcha = \file_get_contents($captcha_url, false, $get_context);

                    $this->logger->info("Getting captcha: $captcha_url, size: ".\strlen($captcha), [
                        'cid' => Coroutine::getCid(),
                    ]);

                    $cookies = \array_merge($cookies, CookieUtilStatic::parse_cookies($http_response_header));

                    if ($captcha && \strlen($captcha) > 100) {
                        if (!\str_contains($captcha, '<html') || \strpos($captcha, '<html') > 30) {
                            $this->logger->info("Captcha loaded successfully ($code)", [
                                'cid' => Coroutine::getCid(),
                            ]);
                        } else {
                            $captcha = 'ERROR_NOT_IMAGE';

                            $this->logger->warning("Captcha not loaded - html received ($code)", [
                                'cid' => Coroutine::getCid(),
                            ]);
                        }
                    } elseif (
                        isset($http_response_header)
                        && \count($http_response_header) > 0
                        && false != \strpos($http_response_header[0], '302')
                    ) {
                        $captcha = 'ERROR_BAD_IMAGE';

                        foreach ($http_response_header as $line) {
                            // @todo откуда берется $server?
                            if (\str_contains($line, 'Location:')) {
                                $captcha_path = \trim(\substr($line, 9));
                                $captcha_url = ('http' == \substr($captcha_path, 0, 4)
                                        ? ''
                                        : $server).$captcha_path;
                                //                            $this->logger->info("Captcha redirect: $captcha_url ($code)");
                                $captcha = '';
                            }
                        }
                    } else {
                        $captcha = 'ERROR_ZERO_IMAGE';

                        $this->logger->warning("Captcha not loaded - answer or redirect expected ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);
                    }

                    if ($proxy) {
                        $success = $captcha && ('ERROR' != \substr($captcha, 0, 5)) ? 1 : 0;

                        $this->connection->executeStatement(
                            "INSERT INTO proxyusage (sourceid,proxyid,success) VALUES ($sourceid,$proxyid,$success)"
                        );

                        if ($success) {
                            $this->connection->executeStatement(
                                "UPDATE proxy SET used=used+1,lasttime=now(),success=success+1,successtime=now() WHERE id=$proxyid"
                            );
                        } else {
                            $this->connection->executeStatement(
                                "UPDATE proxy SET used=used+1,lasttime=now() WHERE id=$proxyid",
                            );

                            $this->logger->info("Proxy $proxyid $proxy failed ($code)");
                        }
                    }

                    if ($captcha && ('ERROR' != \substr($captcha, 0, 5)) && ('base64' == $row['captcha_format'])) {
                        if ($captcha_token_regexp) {
                            if (\preg_match($captcha_token_regexp, $captcha, $matches)) {
                                $token = $matches[1];

                                $this->logger->info("Token $token found ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            } else {
                                $this->logger->warning("Token $captcha_token_regexp not found ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                                //                            $this->logger->info($form);

                                $captcha = false;
                            }
                        }

                        $prefix = 'data:image/jpeg;base64,';
                        $start = \strpos($captcha, $prefix);

                        if (false !== $start) {
                            $captcha = \substr($captcha, $start + \strlen($prefix));
                            $finish = \strpos($captcha, '=');

                            if (false !== $finish) {
                                $captcha = \base64_decode(\substr($captcha, 0, $finish + 1));

                                $this->logger->info("Captcha decoded successfully ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            } else {
                                $captcha = 'ERROR_BASE64';

                                $this->logger->warning("Captcha decoding error ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            }
                        } else {
                            $captcha = 'ERROR_BASE64';

                            $this->logger->warning("Captcha decoding error ($code)", [
                                'cid' => Coroutine::getCid(),
                            ]);
                        }
                    }

                    if ($captcha && ('ERROR' != \substr($captcha, 0, 5)) && ('json' == $row['captcha_format'])) {
                        $json = \json_decode($captcha, true);

                        if ($token_field) {
                            if (\is_array($json) && isset($json[$token_field])) {
                                $token = $json[$token_field];

                                $this->logger->info("Token $token found ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            } else {
                                $this->logger->warning("Token $token_field not found ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                                //                            $this->logger->info($form);

                                $captcha = false;
                            }
                        }

                        if ($captcha_field) {
                            if (\is_array($json) && isset($json[$captcha_field])) {
                                $captcha = \base64_decode($json[$captcha_field]);

                                $this->logger->info("Captcha decoded successfully ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            } else {
                                $captcha = 'ERROR_JSON';

                                $this->logger->warning("Captcha decoding error ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            }
                        }
                    }
                }

                $cookies_str = \addslashes(CookieUtilStatic::cookies_str($cookies));

                if ('ERROR' == \substr((string) $captcha, 0, 5)) {
                    $captcha = false;
                    $captcha_format = false;
                }

                if ($captcha) {
                    $captcha_format = 'image';
                }
                //            $this->logger->info("Format $captcha_format for session: $sessionid ($code)");

                if ($captcha_format) {
                    if (
                        $this->connection->executeStatement(
                            "UPDATE session SET lasttime=now(),endtime=NULL,sessionstatusid=1,statuscode='renew',captchatime=NULL,captcha_reporttime=NULL,captcha=''".($token ? ",token='$token'" : '')." WHERE id=$sessionid"
                        )
                    ) {
                        $this->logger->info("New captcha for session: $sessionid ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);

                        if ($captcha) {
                            $captcha_pic = "captcha/$code/__$sessionid.jpg";

                            $this->loggerUtil->log($captcha_pic, $captcha);
                            //                        file_put_contents("captcha/$code/$sessionid.htm",$http_response_header."\n\n".$form);
                        }
                    } else {
                        $this->logger->warning("Session renewal failed ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);
                    }

                    if ($captcha && isset(NeuroUtil::NEURO_SOURCES[$code])) {
                        $key = '';
                        $host = 'neuro';
                        $antigateid = $this->neuroUtil->neuro_post(
                            $captcha,
                            NeuroUtil::NEURO_SOURCES[$code].'decode'
                        ); // передаем на распознавание
                    } elseif ($captcha) {
                        $key = $antigate_key;
                        $host = $antigate_host;
                        $antigateid = $this->antigateUtil->antigate_post(
                            $captcha,
                            $key,
                            false,
                            $host,
                            0,
                            (int) (2 == $captcha_type),
                            (int) (1 == $captcha_type),
                            $captcha_size,
                            $captcha_size ?: 99,
                            (int) (3 == $captcha_type)
                        ); // передаем на распознавание
                    } elseif ('hcaptcha' == $captcha_format) {
                        $key = $hcaptcha_key;
                        $host = $hcaptcha_host;
                        $antigateid = $this->captchaUtil->captcha_create(
                            $captcha_format,
                            false,
                            $captcha_token,
                            $form_url,
                            $captcha_action,
                            $captcha_minscore,
                            $key,
                            false,
                            $host
                        ); // запрашиваем новый токен
                    } elseif ('v3' == $captcha_format) {
                        $key = $captchav3_key;
                        $host = $captchav3_host;
                        $antigateid = $this->captchaUtil->captcha_create(
                            $captcha_format,
                            false,
                            $captcha_token,
                            $form_url,
                            $captcha_action,
                            $captcha_minscore,
                            $key,
                            false,
                            $host
                        ); // запрашиваем новый токен
                    } else {
                        $key = $captcha_key;
                        $host = $captcha_host;
                        $antigateid = $this->captchaUtil->captcha_create(
                            $captcha_format,
                            false,
                            $captcha_token,
                            $form_url,
                            $captcha_action,
                            $captcha_minscore,
                            $key,
                            false,
                            $host
                        ); // запрашиваем новый токен
                    }

                    if ($antigateid && (!\str_contains((string) $antigateid, 'ERROR'))) {
                        $sessions[$sessionid] = [
                            'sourceid' => $sourceid,
                            'code' => $code,
                            'captcha_format' => $captcha_format,
                            'captcha_type' => $captcha_type,
                            'captcha_size' => $captcha_size,
                            'cookies' => $cookies,
                            'antigatehost' => $host,
                            'antigatekey' => $key,
                            'antigateid' => $antigateid,
                            'starttime' => \microtime(true),
                            'method' => $captcha_check_method,
                            'url' => $captcha_check_url,
                            'params' => $params,
                            'field' => $captcha_field,
                            'token_field' => $token_field,
                            'token' => $token,
                            'token_regexp' => $captcha_check_token_regexp,
                            'proxy' => $proxy,
                            'proxy_auth' => $proxy_auth,
                        ];

                        $this->connection->executeStatement(
                            "UPDATE session SET captcha_service='".$host."'".('neuro' != $host ? ",captcha_id=$antigateid" : '')." WHERE id=$sessionid"
                        );

                        $this->logger->info('Captcha id from '.$host." - $antigateid ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);
                    } else {
                        $this->logger->warning('Failed sending captcha to '.$host." - $antigateid ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);

                        if ($captcha && isset(NeuroUtil::NEURO_SOURCES[$code])) {
                            $key = $antigate_key;
                            $host = $antigate_host;
                            $antigateid = $this->antigateUtil->antigate_post(
                                $captcha,
                                $key,
                                false,
                                $host,
                                0,
                                (int) (2 == $captcha_type),
                                (int) (1 == $captcha_type),
                                $captcha_size,
                                $captcha_size ?: 99,
                                (int) (3 == $captcha_type)
                            ); // передаем на распознавание
                        } elseif ($captcha) {
                            $key = $antigate_key2;
                            $host = $antigate_host2;
                            $antigateid = $this->antigateUtil->antigate_post(
                                $captcha,
                                $key,
                                false,
                                $host,
                                0,
                                (int) (2 == $captcha_type),
                                (int) (1 == $captcha_type),
                                $captcha_size,
                                $captcha_size ?: 99,
                                (int) (3 == $captcha_type)
                            ); // передаем на распознавание
                        } elseif ('hcaptcha' == $captcha_format) {
                            $key = $hcaptcha_key2;
                            $host = $hcaptcha_host2;
                            $antigateid = $this->captchaUtil->captcha_create(
                                $captcha_format,
                                false,
                                $captcha_token,
                                $form_url,
                                $captcha_action,
                                $captcha_minscore,
                                $key,
                                false,
                                $host
                            ); // запрашиваем новый токен
                        } elseif ('v3' == $captcha_format) {
                            $key = $captchav3_key2;
                            $host = $captchav3_host2;
                            $antigateid = $this->captchaUtil->captcha_create(
                                $captcha_format,
                                false,
                                $captcha_token,
                                $form_url,
                                $captcha_action,
                                $captcha_minscore,
                                $key,
                                false,
                                $host
                            ); // запрашиваем новый токен
                        } else {
                            $key = $captcha_key2;
                            $host = $captcha_host2;
                            $antigateid = $this->captchaUtil->captcha_create(
                                $captcha_format,
                                false,
                                $captcha_token,
                                $form_url,
                                $captcha_action,
                                $captcha_minscore,
                                $key,
                                false,
                                $host
                            ); // запрашиваем новый токен
                        }

                        if ($antigateid && (!\str_contains($antigateid, 'ERROR'))) {
                            //                                $channel->push([
                            $sessions[$sessionid] = [
                                'sourceid' => $sourceid,
                                'code' => $code,
                                'captcha_format' => $captcha_format,
                                'captcha_type' => $captcha_type,
                                'captcha_size' => $captcha_size,
                                'cookies' => $cookies,
                                'antigatehost' => $host,
                                'antigatekey' => $key,
                                'antigateid' => $antigateid,
                                'starttime' => \microtime(true),
                                'method' => $captcha_check_method,
                                'url' => $captcha_check_url,
                                'params' => $params,
                                'field' => $captcha_field,
                                'token_field' => $token_field,
                                'token' => $token,
                                'token_regexp' => $captcha_check_token_regexp,
                                'proxy' => $proxy,
                                'proxy_auth' => $proxy_auth,
                            ];

                            $this->connection->executeStatement(
                                "UPDATE session SET captcha_service='".$host."',captcha_id=$antigateid WHERE id=$sessionid"
                            );

                            $this->logger->info('Captcha id from '.$host." - $antigateid ($code)", [
                                'cid' => Coroutine::getCid(),
                            ]);
                        } else {
                            $this->logger->warning('Failed sending captcha to '.$host." - $antigateid ($code)", [
                                'cid' => Coroutine::getCid(),
                            ]);

                            $this->connection->executeStatement(
                                "UPDATE session SET sessionstatusid=4,statuscode='failedcaptcha',endtime=now() WHERE id=$sessionid AND sessionstatusid=1"
                            );
                        }
                    }
                }
            }
        }
    }

    private function readSourceListAndCountOfNeedsSessions(
        float $daemonstarttime,
        string $http_agent,
        int $http_timeout,
        int $max_seconds,
        int $max_sessions,
        bool $timeless,
        string $antigate_host,
        string $antigate_host2,
        string $antigate_key,
        string $antigate_key2,
        string $captcha_host,
        string $captcha_host2,
        string $captcha_key,
        string $captcha_key2,
        string $captchav3_host,
        string $captchav3_key,
        string $hcaptcha_host,
        string $hcaptcha_host2,
        string $hcaptcha_key,
        string $hcaptcha_key2,
        string $proxyfilter,
        string $sourceaccessfilter,
        array $exclude,
        array $include,
        array &$sessions,
    ): int {
        $this->logger->debug('Читаем список источников и количество недостающих сессий по ним');

        $mainResult = $this->connection->executeQuery(
            <<<SQL
SELECT *,
       GREATEST(IFNULL((
         SELECT count(sourceid)
         FROM session
         WHERE sourceid = source.id
           AND sessionstatusid IN (3, 4)
           AND endtime IS NOT NULL
           AND used IS NOT NULL
           AND endtime >= SUBDATE(NOW(), INTERVAL 3 MINUTE)
       ) / 5, 0), min_sessions) - IFNULL((
         SELECT count(sourceid)
         FROM session
         WHERE sourceid = source.id
           AND sessionstatusid IN (1, 2, 7)
       ), 0) count
FROM source
WHERE status = 1
HAVING count >= 1
SQL,
        );

        $res = $mainResult->rowCount();

        $this->logger->debug('Count of sources for processing', [
            'count' => $res,
        ]);

        while (($row = $mainResult->fetchAssociative()) && ($timeless || (\microtime(true) - $daemonstarttime < 300))) {
            $this->logger->debug('Processing source', [
                'source_name' => $row['name'],
                'source_code' => $row['code'],
                'source_url' => $row['url'],
            ]);

            $sourceid = $row['id'];
            $code = $row['code'];

            if (\in_array($code, $exclude, true)
                || (\count($include) > 0 && !\in_array($code, $include, true))
            ) {
                $this->logger->warning('Exclude plugin "'.$code.'" by policy', [
                    'cid' => Coroutine::getCid(),
                ]);

                continue;
            }

            $name = $row['name'];
            $count = (int) $row['count'];

            $this->logger->info("Sessions required: $count ($code)", [
                'cid' => Coroutine::getCid(),
            ]);

            $url = $row['url'];
            $useragent = '' == $row['useragent'] ? $http_agent : $row['useragent'];
            $codepage = $row['codepage'];
            $proxy = $row['proxy'];
            $proxygroup = $row['proxygroup'];
            $proxy_sessions = $row['proxy_sessions'];
            $login_form_path = $row['login_form_path'];
            $login_form_url = ('http' == \substr($login_form_path, 0, 4) ? '' : $url).$login_form_path;
            $login_post_path = $row['login_post_path'];
            $login_post_url = ('http' == \substr($login_post_path, 0, 4) ? '' : $url).$login_post_path;
            $login_field = $row['login_field'];
            $password_field = $row['password_field'];
            $other_fields = $row['other_fields'];
            $auth_path = '';
            $login_locked_regexp = $row['login_locked_regexp'];
            $auth_path_regexp = $row['auth_path_regexp'];
            $logoff_path = $row['logoff_path'];
            $form_path = $row['form_path'];
            $form_url = '' == $form_path ? false : (('http' == \substr($form_path, 0, 4) ? '' : $url).$form_path);
            $form_regexp = $row['form_regexp'];
            $form_header = $row['form_header'];
            $post_method = $row['post_method'];
            $post_path = $row['post_path'];
            $post_url = ('http' == \substr($post_path, 0, 4) ? '' : $url).$post_path;
            $captcha_path = $row['captcha_path'];
            $captcha_path_regexp = $row['captcha_path_regexp'];
            $captcha_token = $row['captcha_token'];
            $captcha_token_regexp = $row['captcha_token_regexp'];
            $captcha_action = $row['captcha_action'];
            $captcha_minscore = $row['captcha_minscore'];
            $captcha_format = $row['captcha_format'];
            $captcha_type = $row['captchatypeid'];
            $captcha_size = $row['captcha_size'];
            $captcha_check_method = $row['captcha_check_method'];
            $captcha_check_path = $row['captcha_check_path'];
            $captcha_check_url = $captcha_check_path;

            if ($captcha_check_path) {
                $captcha_check_url = ('http' == \substr(
                    $captcha_check_path,
                    0,
                    4
                ) ? '' : $url).$captcha_check_path;
            }

            $captcha_check_token_regexp = $row['captcha_check_token_regexp'];
            $captcha_field = $row['captcha_field'];
            $token_field = $row['token_field'];

            if ($count > 20 && \count($sessions)) {
                $count = 20;
            }

            if ($count && $login_post_path) {
                $count = 1;
            }

            if ($count > $max_sessions) {
                $count = $max_sessions;
            }

            $this->logger->info("Sessions will be created: $count ($code)", [
                'cid' => Coroutine::getCid(),
            ]);

            if (
                $captcha_check_url
                && (
                    !$proxy
                    && !$proxygroup
                )
            ) {
                $count = 1;

                foreach ($sessions as $sessionid => &$s) {
                    if ($s['code'] == $code) {
                        $count = 0;
                    }
                }

                unset($s);
            }

            $this->logger->info('Создаем новые сессии', [
                'cid' => Coroutine::getCid(),
            ]);

            $starttime = \microtime(true);

            while ($count-- > 0 && ($timeless || \microtime(true) - $starttime < $max_seconds)) {
                $sql = <<<SQL
SELECT GREATEST(IFNULL((
         SELECT count(sourceid)
         FROM session
         WHERE sourceid = source.id
           AND sessionstatusid IN (3, 4)
           AND endtime IS NOT NULL
           AND used IS NOT NULL
           AND endtime >= SUBDATE(NOW(), INTERVAL 3 MINUTE)
       ) / 5, 0), min_sessions) - IFNULL((
         SELECT count(sourceid)
         FROM session
         WHERE sourceid = source.id
           AND sessionstatusid IN (1, 2, 7)
       ), 0) count
FROM source
WHERE id = {$sourceid}
SQL;

                $result = $this->connection->executeQuery($sql);

                if (0 === (float) $result->fetchOne()) {
                    $count = 0;
                    break;
                }

                $server = '';
                $cookies = CookieUtilStatic::str_cookies($row['cookies']);
                $auth_url = false;
                $login = false;
                $login_post = false;
                $form = false;
                $captcha_url = $captcha_path;
                $captcha = false;
                $token = '';
                $sourceaccessid = false;
                $params = [];

                $proxy = $row['proxy'];
                $proxy_auth = false;

                if (-1 == $proxy) { // Сначала проверяем есть ли сессия без прокси
                    $sql = <<<SQL
SELECT COUNT(*) count
FROM session
WHERE sourceid = {$sourceid}
  AND endtime IS NULL
  AND proxyid IS NULL
SQL;

                    $noproxy_result = $this->connection->executeQuery($sql);

                    if ($noproxy_row = $noproxy_result->fetchAssociative()) {
                        if (0 == $noproxy_row['count']) {
                            $proxy = 0;
                        } // Можно создать сессию без прокси
                    }
                }

                if ($proxy) {
                    // выбираем активные прокси, кроме тех, которые с этим источником за последний час успешно работали менее чем в 50% случаев
                    $sql = 'SELECT * FROM proxy';

                    if ($proxy < 0) { // Выбираем прокси, по которому еще нет сессии
                        $sql .= " WHERE status > 0
                                        AND enabled > 0
                                        AND id NOT IN (
                                          SELECT proxyid
                                          FROM proxysourcehourstats
                                          WHERE sourceid = {$sourceid}
                                            AND successrate < 0.5
                                        )";
                        //                    if ($proxy_sessions) $sql .= " AND id not in (SELECT proxyid FROM session WHERE sourceid=$sourceid AND endtime IS NULL AND proxyid IS NOT NULL)";

                        if ($proxy_sessions) {
                            $sql .= " AND (
                                            rotation > 0
                                            OR (
                                              SELECT COUNT(*)
                                              FROM session
                                              WHERE proxyid = proxy.id
                                                AND sourceid = {$sourceid}
                                                AND sessionstatusid IN (1, 2, 6, 7)
                                            ) < {$proxy_sessions}
                                          )";
                        }

                        if ($proxygroup) {
                            $sql .= " AND proxygroup = {$proxygroup}";
                        }

                        $sql .= $proxyfilter;
                    } else {
                        $sql .= " WHERE id = {$proxy}";
                    }

                    $sql .= ' ORDER BY lasttime LIMIT 1';
                    $proxy_result = $this->connection->executeQuery($sql);

                    if ($proxy_row = $proxy_result->fetchAssociative()) {
                        $proxyid = $proxy_row['id'];
                        $proxy = 'tcp://'.$proxy_row['server'].':'.$proxy_row['port'];

                        if ($proxy_row['login']) {
                            $proxy_auth = \base64_encode($proxy_row['login'].':'.$proxy_row['password']);
                        } else {
                            $proxy_auth = false;
                        }

                        $this->logger->info("Selected proxy $proxyid $proxy ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);
                        $this->connection->executeStatement(
                            "
                                UPDATE proxy
                                SET lasttime = now()
                                WHERE id = {$proxyid}
                            "
                        );
                    } else {
                        $proxy = 0;
                        $count = 0;

                        $this->logger->warning("Not enough proxies ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);

                        break;
                    }
                }

                if ($login_post_path) {
                    $sql = <<<SQL
SELECT *
FROM sourceaccess
WHERE sourceid = {$sourceid}
  AND status = 1
  AND (
    unlocktime IS NULL
    OR unlocktime < now()
  )
  AND unix_timestamp(now()) - unix_timestamp(lasttime) > 30
  AND sourceaccessid not in (
    SELECT sourceaccessid
    FROM session
    WHERE endtime IS NULL
      AND sourceaccessid IS NOT NULL
  )
  {$sourceaccessfilter}
ORDER BY lasttime
SQL;

                    $access_result = $this->connection->executeQuery($sql);

                    if ($access_row = $access_result->fetchAssociative()) {
                        $sourceaccessid = $access_row['sourceaccessid'];
                        $login = $access_row['login'];
                        $password = $access_row['password'];

                        $this->connection->executeStatement(
                            "UPDATE sourceaccess SET lasttime=now() WHERE sourceaccessid=$sourceaccessid"
                        );

                        $params = [];

                        $this->logger->info("Log in as $login ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);

                        if ($login_form_path) {
                            $get_options = [
                                'http' => [
                                    'method' => 'GET',
                                    'timeout' => $http_timeout,
                                    'follow_location' => 0,
                                    'header' => "User-Agent: $useragent\r\n".
                                        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n".
                                        "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3\r\n".
                                        "DNT: 1\r\n".
                                        "Connection: keep-alive\r\n".
                                        "Upgrade-Insecure-Requests: 1\r\n".
                                        CookieUtilStatic::cookies_header($cookies),
                                ],
                                'ssl' => [
                                    'verify_peer' => false,
                                    'verify_peer_name' => false,
                                ],
                            ];

                            if ($proxy) {
                                $get_options['http']['proxy'] = $proxy;
                                $get_options['http']['request_fulluri'] = true;
                                if ($proxy_auth) {
                                    $get_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                                }
                                $this->logger->info("[2] Using proxy $proxyid $proxy ($code)");
                            }

                            $get_context = \stream_context_create($get_options);
                            $login_form = \file_get_contents($login_form_url, false, $get_context);

                            $this->logger->info(
                                "Getting login form: $login_form_url, size: ".\strlen((string) $login_form), [
                                    'cid' => Coroutine::getCid(),
                                ]
                            );
                            //                        $this->logger->info($get_options['http']['header']);

                            $redirects = 0;
                            $next_url = $login_form_url;

                            while (
                                /* !$login_form && */ isset($http_response_header) && \count(
                                    $http_response_header
                                ) > 0 && (false != \strpos($http_response_header[0], '500') || false != \strpos(
                                    $http_response_header[0],
                                    '307'
                                ) || false != \strpos($http_response_header[0], '303') || false != \strpos(
                                    $http_response_header[0],
                                    '302'
                                ) || false != \strpos(
                                    $http_response_header[0],
                                    '301'
                                )
                                ) && (++$redirects < 10) /* && (microtime(true)-$starttime < $max_seconds) */
                            ) {
                                //                            $this->logger->info("\n\n".(isset($http_response_header)?implode("\n",$http_response_header):''));
                                $cookies = \array_merge($cookies, CookieUtilStatic::parse_cookies($http_response_header));
                                //                            $this->logger->info("Cookies: ".cookies_header($cookies));

                                foreach ($http_response_header as $line) {
                                    if (\str_contains($line, 'Location:')) {
                                        $next_path = \trim(\substr($line, 9));
                                        $purl = \parse_url($next_url);
                                        $server = $purl['scheme'].'://'.$purl['host'];
                                        if (\array_key_exists('port', $purl)) {
                                            $server .= ':'.$purl['port'];
                                        }
                                        $next_url = ('http' == \substr(
                                            $next_path,
                                            0,
                                            4
                                        ) ? '' : $server).$next_path;
                                        $this->logger->info("Login form redirect: $next_url ($code)", [
                                            'cid' => Coroutine::getCid(),
                                        ]);
                                    }
                                }

                                $get_options['http']['header'] =
                                    "User-Agent: $useragent\r\n".
                                    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n".
                                    "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3\r\n".
                                    "DNT: 1\r\n".
                                    "Connection: keep-alive\r\n".
                                    "Upgrade-Insecure-Requests: 1\r\n".
                                    CookieUtilStatic::cookies_header($cookies);
                                if ($proxy && $proxy_auth) {
                                    $get_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                                }
                                $get_context = \stream_context_create($get_options);

                                $login_form = \file_get_contents($next_url, false, $get_context);
                                $this->logger->info("Getting login form: $next_url, size: ".\strlen($login_form), [
                                    'cid' => Coroutine::getCid(),
                                ]);
                                //                            $this->logger->info($get_options['http']['header']);
                            }

                            if ($login_form) {
                                $cookies = \array_merge($cookies, CookieUtilStatic::parse_cookies($http_response_header));
                                //                            $this->logger->info("Cookies: ".cookies_header($cookies));
                                if ($codepage) {
                                    $login_form = \iconv($codepage, 'utf-8', $login_form);
                                }
                                $this->loggerUtil->log(
                                    "logs/$code/login_form.htm",
                                    (isset($http_response_header) ? \implode(
                                        "\n",
                                        $http_response_header
                                    ) : '')."\n\n".$login_form
                                );
                                if (
                                    \preg_match_all(
                                        '/<input[^>]+name="([^"]+)[^>]+value="([^"]+)[^>]+>/',
                                        $login_form,
                                        $matches
                                    )
                                ) {
                                    foreach ($matches[1] as $i => $v) {
                                        if (!isset($params[$v])) {
                                            $params[$v] = $matches[2][$i];
                                            $this->logger->info("Parameter $v = ".$params[$v], [
                                                'cid' => Coroutine::getCid(),
                                            ]);
                                        }
                                    }
                                }
                                if (
                                    \preg_match_all(
                                        '/<input[^>]+value="([^"]+)[^>]+name="([^"]+)[^>]+>/',
                                        $login_form,
                                        $matches
                                    )
                                ) {
                                    foreach ($matches[1] as $i => $v) {
                                        if (!isset($params[$matches[2][$i]])) {
                                            $params[$matches[2][$i]] = $v;
                                            $this->logger->info('Parameter '.$matches[2][$i].' = '.$v, [
                                                'cid' => Coroutine::getCid(),
                                            ]);
                                        }
                                    }
                                }
                            }

                            if ($proxy) {
                                $success = $login_form ? 1 : 0;
                                $this->connection->executeStatement(
                                    "INSERT INTO proxyusage (sourceid,proxyid,success) VALUES ($sourceid,$proxyid,$success)"
                                );
                                if ($success) {
                                    $this->connection->executeStatement(
                                        "UPDATE proxy SET used=used+1,lasttime=now(),success=success+1,successtime=now() WHERE id=$proxyid"
                                    );
                                } else {
                                    $this->connection->executeStatement(
                                        "UPDATE proxy SET used=used+1,lasttime=now() WHERE id=$proxyid"
                                    );
                                    $this->logger->warning("Proxy $proxyid $proxy failed ($code)", [
                                        'cid' => Coroutine::getCid(),
                                    ]);
                                }
                            }
                        }

                        if (!$login_form_path || $login_form) {
                            $params[$login_field] = $login;
                            $params[$password_field] = $password;
                            $post_data = \http_build_query($params);
                            //                        $post_data = $login_field.'='.$login.'&'.$password_field.'='.$password;
                            if ($other_fields) {
                                $post_data .= '&'.$other_fields;
                            }
                            $post_options = [
                                'http' => [
                                    'method' => 'POST',
                                    'content' => $post_data,
                                    'timeout' => $http_timeout,
                                    'follow_location' => 0,
                                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                                        'Content-Length: '.\strlen($post_data)."\r\n".
                                        "X-Requested-With: XMLHttpRequest\r\n".
                                        "Cache-Control: no-cache, no-store, must-revalidate\r\n".
                                        //                                "Connection: keep-alive\r\n" .
                                        "User-Agent: $useragent\r\n".
                                        "Origin: $url\r\n".
                                        "Referer: $login_form_url\r\n".
                                        CookieUtilStatic::cookies_header($cookies),
                                ],
                                'ssl' => [
                                    'verify_peer' => false,
                                    'verify_peer_name' => false,
                                    //                            'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
                                ],
                            ];
                            if ($proxy) {
                                $post_options['http']['proxy'] = $proxy;
                                $post_options['http']['request_fulluri'] = true;
                                if ($proxy_auth) {
                                    $post_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                                }
                                $this->logger->info("[3] Using proxy $proxyid $proxy ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            }
                            $post_context = \stream_context_create($post_options);
                            $login_post = \file_get_contents($login_post_url, false, $post_context);
                            $this->logger->info("Posting: $login_post_url, size: ".\strlen($login_post), [
                                'cid' => Coroutine::getCid(),
                            ]);
                            $this->loggerUtil->log(
                                "logs/$code/login_$login.htm",
                                (isset($http_response_header) ? \implode(
                                    "\n",
                                    $http_response_header
                                ) : '')."\n\n".$login_post
                            );

                            $redirects = 0;
                            $next_url = $login_post_url;
                            while (
                                /* !$login_post && */ isset($http_response_header) && \count(
                                    $http_response_header
                                ) > 0 && (false != \strpos($http_response_header[0], '500') || false != \strpos(
                                    $http_response_header[0],
                                    '307'
                                ) || false != \strpos($http_response_header[0], '303') || false != \strpos(
                                    $http_response_header[0],
                                    '302'
                                ) || false != \strpos(
                                    $http_response_header[0],
                                    '301'
                                )
                                ) && (++$redirects < 10) /* && (microtime(true)-$starttime < $max_seconds) */
                            ) {
                                $cookies = \array_merge($cookies, CookieUtilStatic::parse_cookies($http_response_header));
                                foreach ($http_response_header as $line) {
                                    if (\str_contains($line, 'Location:')) {
                                        $next_path = \trim(\substr($line, 9));
                                        $purl = \parse_url($login_post_url);
                                        $server = $purl['scheme'].'://'.$purl['host'];
                                        if (\array_key_exists('port', $purl)) {
                                            $server .= ':'.$purl['port'];
                                        }
                                        $next_url = ('http' == \substr(
                                            $next_path,
                                            0,
                                            4
                                        ) ? '' : $server).$next_path;
                                        $this->logger->info("Login redirect: $next_url ($code)", [
                                            'cid' => Coroutine::getCid(),
                                        ]);
                                    }
                                }

                                $get_options = [
                                    'http' => [
                                        'timeout' => $http_timeout,
                                        'follow_location' => 0,
                                        'header' => "User-Agent: $useragent\r\n".
                                            "Referer: $login_form_url\r\n".
                                            CookieUtilStatic::cookies_header($cookies),
                                    ],
                                    'ssl' => [
                                        'verify_peer' => false,
                                        'verify_peer_name' => false,
                                        //                                'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
                                    ],
                                ];
                                if ($proxy) {
                                    $get_options['http']['proxy'] = $proxy;
                                    $get_options['http']['request_fulluri'] = true;
                                    if ($proxy_auth) {
                                        $get_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                                    }
                                }
                                $get_context = \stream_context_create($get_options);
                                $login_post = \file_get_contents($next_url, false, $get_context);
                            }
                        }
                    } else {
                        //                    $this->logger->info("Not enough accounts ($code)");
                        $count = 0;
                        $auth_path = false;
                        $auth_url = false;
                        $form_path = false;
                        $form_url = false;
                        break;
                    }

                    if ($login_post) {
                        if ($codepage) {
                            $login_post = \iconv($codepage, 'utf-8', $login_post);
                        }
                        $this->loggerUtil->log(
                            "logs/$code/logged_$login.htm",
                            (isset($http_response_header) ? \implode(
                                "\n",
                                $http_response_header
                            ) : '')."\n\n".$login_post
                        );
                        $cookies = \array_merge($cookies, CookieUtilStatic::parse_cookies($http_response_header));
                        //                    $this->logger->info("Cookies: ".cookies_header($cookies));
                        /* !!!!! */
                        if ($captcha_path_regexp) {
                            if (\preg_match($captcha_path_regexp, $login_post, $matches)) {
                                $captcha_url .= $matches[1];
                                $captcha = false;
                                $this->logger->info("Captcha URL: $captcha_url ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            }
                        }

                        if ($captcha_token_regexp) {
                            if (\preg_match($captcha_token_regexp, $login_post, $matches)) {
                                $token = $matches[1];
                                $this->logger->info("Token $token found ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            } else {
                                $captcha_url = false;
                            }
                        }
                        /* !!!!! */
                        if ($login_locked_regexp && \preg_match($login_locked_regexp, $login_post, $matches)) {
                            $this->logger->info("User $login is locked for 2 hours ($code)", [
                                'cid' => Coroutine::getCid(),
                            ]);
                            $this->connection->executeStatement(
                                "UPDATE sourceaccess SET unlocktime=date_add(now(),interval 2 hour) WHERE sourceaccessid=$sourceaccessid"
                            );
                        }
                        if ($auth_path_regexp) {
                            if (\preg_match($auth_path_regexp, $login_post, $matches)) {
                                $auth_path = $matches[1];
                                $this->logger->info("Authentication path: $auth_path ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                                if ('http' == \substr($auth_path, 0, 4)) {
                                    $auth_url = $auth_path;
                                    $purl = \parse_url($auth_url);
                                    $server = $purl['scheme'].'://'.$purl['host'];
                                    if (\array_key_exists('port', $purl)) {
                                        $server .= ':'.$purl['port'];
                                    }
                                    //                                if ($server!=$url) $cookies = array();
                                    $form_url = ('http' == \substr($form_path, 0, 4) ? '' : $server).$form_path;
                                } else {
                                    $auth_url = $url.$auth_path;
                                    $server = $url;
                                }
                            } else {
                                $auth_path = false;
                                $auth_url = false;
                                $form_path = false;
                                $form_url = false;
                                $this->logger->warning("Authentication path not found ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            }
                        }
                    } else {
                        $auth_path = false;
                        $auth_url = false;
                        $form_path = false;
                        $form_url = false;
                        $count = 0;
                        break;
                    }
                }

                while ($auth_url) {
                    $get_options = [
                        'http' => [
                            'method' => 'GET',
                            'timeout' => $http_timeout,
                            'follow_location' => 0,
                            //                    'max_redirects' => 10,
                            'header' =>
                            //                        "X-Requested-With: XMLHttpRequest\r\n" .
//                        "Cache-Control: no-cache, no-store, must-revalidate\r\n" .
//                        "Connection: keep-alive\r\n" .
                                "User-Agent: $useragent\r\n".
                                //                        "Referer: $login_form_url\r\n" .
//                        "Upgrade-Insecure-Requests: 1\r\n" .
                                CookieUtilStatic::cookies_header($cookies),
                        ],
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                        ],
                    ];
                    if ($proxy) {
                        $get_options['http']['proxy'] = $proxy;
                        $get_options['http']['request_fulluri'] = true;
                        if ($proxy_auth) {
                            $get_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                        }
                        $this->logger->info("[4] Using proxy $proxyid $proxy ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);
                    }
                    $get_context = \stream_context_create($get_options);
                    $auth = \file_get_contents($auth_url, false, $get_context);
                    $this->logger->info("Getting: $auth_url, size: ".\strlen($auth), [
                        'cid' => Coroutine::getCid(),
                    ]);
                    $cookies = \array_merge($cookies, CookieUtilStatic::parse_cookies($http_response_header));
                    if ($auth) {
                        if ($codepage) {
                            $auth = \iconv($codepage, 'utf-8', $auth);
                        }
                        $this->loggerUtil->log(
                            "logs/$code/auth_$login.htm",
                            (isset($http_response_header) ? \implode(
                                "\n",
                                $http_response_header
                            ) : '')."\n\n".$auth
                        );

                        if ($logoff_path && false == \strpos($auth, $logoff_path)) {
                            $auth_url = false;
                            $auth_path = false;
                            $form_url = false;
                            $form_path = false;
                            $this->logger->warning("Authentification failed - logoff not found ($code)", [
                                'cid' => Coroutine::getCid(),
                            ]);
                        } else {
                            $auth_url = false;
                        }
                    } elseif (
                        isset($http_response_header) && \count($http_response_header) > 0 && false != \strpos(
                            $http_response_header[0],
                            '302'
                        )
                    ) {
                        $auth_url = false;
                        $auth_path = false;
                        foreach ($http_response_header as $line) {
                            if (\str_contains($line, 'Location:')) {
                                $auth_path = \trim(\substr($line, 9));
                                $auth_url = ('http' == \substr($auth_path, 0, 4) ? '' : $server).$auth_path;
                                //                            $this->logger->info("Authentication redirect: $auth_url ($code)");
                            }
                        }
                    } else {
                        $auth_url = false;
                        $auth_path = false;
                        $form_url = false;
                        $form_path = false;
                        $this->logger->warning("Authentification failed - answer or redirect expected ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);
                    }
                }

                if ($form_url) {
                    $params = [];

                    $extra_headers = [];
                    foreach (\explode("\r\n", $form_header."\r\n".CookieUtilStatic::cookies_header($cookies)) as $headerLine) {
                        if (empty($headerLine)) {
                            continue;
                        }
                        $components = \array_map('trim', \explode(':', $headerLine, 2));
                        $extra_headers[$components[0]] = $components[1];
                    }

                    $requestOptions = [
                        RequestOptions::TIMEOUT => $http_timeout,
                        RequestOptions::ALLOW_REDIRECTS => false,
                        RequestOptions::HEADERS => [
                            'User-Agent' => $useragent,
                            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                            'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
                            'DNT' => '1',
                            'Connection' => 'keep-alive',
                            'Upgrade-Insecure-Requests' => '1',
                            'Sec-Fetch-Dest' => 'document',
                            'Sec-Fetch-Mode' => 'navigate',
                            'Sec-Fetch-Site' => 'none',
                            'Sec-Fetch-User' => '?1',
                            ...$extra_headers,
                        ],
                        RequestOptions::VERIFY => false,
                    ];

                    if ($proxy) {
                        $this->logger->debug('Checking for proxy respond', [
                            'proxy' => $proxy,
                        ]);

                        $components = \parse_url($proxy);
                        $connection = @\fsockopen($components['host'], $components['port'], timeout: $http_timeout);

                        if (!\is_resource($connection)) {
                            $this->logger->warning(\sprintf('%s is not responding', $proxy));

                            continue;
                        }

                        \fclose($connection);

                        $requestOptions[RequestOptions::PROXY] = \str_replace(['tcp://', 'https://'], 'http://', $proxy);
                        $requestOptions[RequestOptions::STREAM] = true;

                        if ($proxy_auth) {
                            $requestOptions[RequestOptions::HEADERS]['Authorization'] = 'Basic '.$proxy_auth;
                            $requestOptions[RequestOptions::HEADERS]['Proxy-Authorization'] = 'Basic '.$proxy_auth;
                        }

                        $this->logger->info("[5] Using proxy $proxyid $proxy ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);
                    }

                    $http_response_header = [];

                    try {
                        $response = $this->client->get($form_url, $requestOptions);
                        $form = $response->getBody()->getContents();
                        $ref = new \ReflectionClass(Response::class);
                        $constants = $ref->getConstants();
                        $key = \array_search($response->getStatusCode(), $constants, true);
                        if (false !== $key && \str_starts_with($key, 'HTTP_')) {
                            $key = \str_replace('HTTP_', '', $key);
                            $key = \str_replace('_', ' ', $key);
                            $http_response_header[] = 'HTTP/'.$response->getProtocolVersion().' '.$response->getStatusCode().' '.$key;
                        }
                        foreach ($response->getHeaders() as $k => $values) {
                            foreach ($values as $value) {
                                $http_response_header[] = $k.': '.$value;
                            }
                        }
                    } catch (\Throwable $e) {
                        $this->logger->warning('[5] Unable to processing source: '.$e->getMessage(), [
                            'cid' => Coroutine::getCid(),
                            'form_url' => $form_url,
//                            ...ExceptionUtilStatic::props($e),
                        ]);

                        continue;
                    }

                    $this->logger->info("Getting form: $form_url, size: ".\strlen($form), [
                        'cid' => Coroutine::getCid(),
                    ]);

                    $this->loggerUtil->log(
                        "logs/$code/form".($login ? '_'.$login : '').'.htm',
                        (isset($http_response_header) ? \implode("\n", $http_response_header) : '')."\n\n".$form
                    );

                    $redirects = 0;
                    $next_url = $form_url;

                    while (
                        isset($http_response_header)
                        && \count($http_response_header) > 0
                        && (
                            false != \strpos($http_response_header[0], '500')
                            || false != \strpos($http_response_header[0], '403')
                            || false != \strpos($http_response_header[0], '307')
                            || false != \strpos($http_response_header[0], '303')
                            || false != \strpos($http_response_header[0], '302')
                            || false != \strpos($http_response_header[0], '301')
                        )
                        && (++$redirects < 10)
                        && (\microtime(true) - $starttime < $max_seconds)
                    ) {
                        $this->logger->info('Header: '.\implode("\n", $http_response_header), [
                            'cid' => Coroutine::getCid(),
                        ]);

                        $cookies = \array_merge($cookies, CookieUtilStatic::parse_cookies($http_response_header));

                        $this->logger->info('Cookies: '.CookieUtilStatic::cookies_header($cookies), [
                            'cid' => Coroutine::getCid(),
                        ]);

                        foreach ($http_response_header as $line) {
                            if (\str_contains($line, 'Location:')) {
                                $next_path = \trim(\substr($line, 9));
                                $purl = \parse_url($form_url);
                                $server = $purl['scheme'].'://'.$purl['host'];
                                if (\array_key_exists('port', $purl)) {
                                    $server .= ':'.$purl['port'];
                                }
                                $next_url = ('http' == \substr($next_path, 0, 4) ? '' : $server).$next_path;
                                $this->logger->info("Form redirect: $next_url ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            }
                        }

                        $get_options['http']['header'] =
                            "User-Agent: $useragent\r\n".
                            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n".
                            "Accept-Encoding: identity\r\n".
                            "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3\r\n".
                            "DNT: 1\r\n".
                            "Connection: keep-alive\r\n".
                            "Upgrade-Insecure-Requests: 1\r\n".
                            ($form_header ? $form_header."\r\n" : '').
                            CookieUtilStatic::cookies_header($cookies);
                        if ($proxy && $proxy_auth) {
                            $get_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                        }
                        $get_context = \stream_context_create($get_options);

                        $form = \file_get_contents($next_url, false, $get_context);
                        $this->logger->info("Getting form: $next_url, size: ".\strlen($form), [
                            'cid' => Coroutine::getCid(),
                        ]);
                    }

                    if ($proxy) {
                        $success = $form ? 1 : 0;

                        $stmt = $this->connection->prepare(
                            <<<'SQL'
INSERT INTO proxyusage (sourceid, proxyid, success)
VALUES (:sourceid, :proxyid, :success)
SQL,
                        );
                        $stmt->bindValue('sourceid', $sourceid);
                        $stmt->bindValue('proxyid', $proxyid);
                        $stmt->bindValue('success', $success);
                        $stmt->executeStatement();

                        if ($success) {
                            $stmt = $this->connection->prepare(
                                <<<'SQL'
UPDATE proxy
SET used = used + 1,
    lasttime = now(),
    success = success + 1,
    successtime = now()
WHERE id = :proxyid
SQL,
                            );
                            $stmt->bindValue('proxyid', $proxyid);
                            $stmt->executeStatement();
                        } else {
                            $stmt = $this->connection->prepare(
                                <<<'SQL'
UPDATE proxy
SET used = used + 1,
    lasttime = now()
WHERE id = :proxyid
SQL,
                            );
                            $stmt->bindValue('proxyid', $proxyid);
                            $stmt->executeStatement();

                            $this->logger->warning("Proxy $proxyid $proxy failed ($code)", [
                                'cid' => Coroutine::getCid(),
                            ]);

                            $post_path = false;
                        }
                    }

                    if ($form) {
                        if ($codepage) {
                            $form = \iconv($codepage, 'utf-8', $form);
                        }

                        $this->loggerUtil->log(
                            "logs/$code/form".($login ? '_'.$login : '').'.htm',
                            (isset($http_response_header)
                                ? \implode("\n", $http_response_header)
                                : ''
                            )."\n\n".$form
                        );

                        $cookies = \array_merge($cookies, CookieUtilStatic::parse_cookies($http_response_header ?? []));

                        $this->logger->info('Cookies: '.CookieUtilStatic::cookies_header($cookies), [
                            'cid' => Coroutine::getCid(),
                        ]);

                        if (
                            \preg_match_all(
                                '/<input[^>]+name="([^"]+)[^>]+value="([^"]+)[^>]+>/',
                                $form,
                                $matches
                            )
                        ) {
                            foreach ($matches[1] as $i => $v) {
                                if (!isset($params[$v])) {
                                    $params[$v] = $matches[2][$i];

                                    $this->logger->info("Parameter $v = ".$params[$v], [
                                        'cid' => Coroutine::getCid(),
                                    ]);
                                }
                            }
                        }

                        if (
                            \preg_match_all(
                                '/<input[^>]+value="([^"]+)[^>]+name="([^"]+)[^>]+>/',
                                $form,
                                $matches
                            )
                        ) {
                            foreach ($matches[1] as $i => $v) {
                                if (!isset($params[$matches[2][$i]])) {
                                    $params[$matches[2][$i]] = $v;

                                    $this->logger->info('Parameter '.$matches[2][$i].' = '.$v, [
                                        'cid' => Coroutine::getCid(),
                                    ]);
                                }
                            }
                        }

                        if ($captcha_path_regexp) {
                            if (\preg_match($captcha_path_regexp, $form, $matches)) {
                                $captcha_url .= $matches[1];
                                $captcha = false;
                                $this->logger->info("Captcha URL: $captcha_url ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            } else {
                                //                            $captcha_url = false;
                                $this->logger->warning("Captcha path $captcha_path_regexp not found ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                                $this->loggerUtil->log(
                                    "logs/$code/nocaptcha_".($login ? '_'.$login : '').'.htm',
                                    (isset($http_response_header) ? \implode(
                                        "\n",
                                        $http_response_header
                                    ) : '')."\n\n".$form
                                );
                                //                            $this->logger->info($form);
                            }
                        }

                        if ($captcha_token_regexp) {
                            if (\preg_match($captcha_token_regexp, $form, $matches)) {
                                $token = $matches[1];
                                $this->logger->info("Token $token found ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            } else {
                                $this->logger->warning("Token $captcha_token_regexp not found ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                                //                            $this->logger->info($form);
                                $form = false;
                            }
                        }
                    }

                    if (
                        $form
                        && (
                            !$logoff_path
                            || \strpos($form, $logoff_path)
                        )
                        && (
                            !$form_regexp
                            || \preg_match($form_regexp, $form)
                        )
                    ) {
                    } elseif ($login_post) {
                        $auth_url = false;
                        $auth_path = false;

                        $this->logger->info(
                            "Logoff path, token or form not found, user $login is locked for 2 hours ($code)", [
                                'cid' => Coroutine::getCid(),
                            ]
                        );

                        $stmt = $this->connection->prepare(
                            <<<'SQL'
UPDATE sourceaccess
SET unlocktime = date_add(now(), interval 2 hour)
WHERE sourceaccessid = :sourceaccessid
SQL,
                        );
                        $stmt->bindValue('sourceaccessid', $sourceaccessid);
                        $stmt->executeStatement();
                    }
                }

                if ($post_path) {
                    $params = [];
                    $post_data = \http_build_query($params);
                    $post_options = [
                        'http' => [
                            'method' => 'POST',
                            'content' => $post_data,
                            'timeout' => $http_timeout,
                            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                                'Content-Length: '.\strlen($post_data)."\r\n".
                                "X-Requested-With: XMLHttpRequest\r\n".
                                "Cache-Control: no-cache, no-store, must-revalidate\r\n".
                                //                        "Connection: keep-alive\r\n" .
                                "User-Agent: $useragent\r\n".
                                "Origin: $url\r\n".
                                "Referer: $form_url\r\n".
                                CookieUtilStatic::cookies_header($cookies),
                        ],
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                        ],
                    ];
                    if ($proxy) {
                        $post_options['http']['proxy'] = $proxy;
                        $post_options['http']['request_fulluri'] = true;
                        if ($proxy_auth) {
                            $post_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                        }
                        $this->logger->info("[6] Using proxy $proxyid $proxy ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);
                    }
                    $post_context = \stream_context_create($post_options);
                    $post = \file_get_contents($post_url, false, $post_context);
                    $this->logger->info("Posting: $post_url, size: ".\strlen($post), [
                        'cid' => Coroutine::getCid(),
                    ]);
                    $this->loggerUtil->log(
                        "logs/$code/post".($login ? '_'.$login : '').'.htm',
                        (isset($http_response_header) ? \implode("\n", $http_response_header) : '')."\n\n".$post
                    );
                    $cookies = \array_merge($cookies, CookieUtilStatic::parse_cookies($http_response_header));
                }

                /* !!!!! */
                while (
                    (
                        $form
                        || $login_post
                    )
                    && $captcha_url
                    && !$captcha
                    /* && (microtime(true)-$starttime < $max_seconds) */
                ) {
                    /* !!!!! */
                    $captcha_url = ('http' == \substr($captcha_url, 0, 4) ? '' : $url).$captcha_url;
                    $get_options = [
                        'http' => [
                            'method' => 'GET',
                            'timeout' => $http_timeout,
                            'follow_location' => 0,
                            'header' =>
                            /*
                            "Cache-Control: no-cache, no-store, must-revalidate\r\n" .
                            //                        "Connection: keep-alive\r\n" .
                            "User-Agent: $useragent\r\n" .
                            */
                                "User-Agent: $useragent\r\n".
                                "Accept: */*\r\n".
                                //                        "Accept-Encoding: identity\r\n" .
                                "Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3\r\n".
                                "DNT: 1\r\n".
                                "Connection: keep-alive\r\n".
                                "Referer: $form_url\r\n".
                                CookieUtilStatic::cookies_header($cookies).
                                "Upgrade-Insecure-Requests: 1\r\n".
                                "Sec-Fetch-Dest: script\r\n".
                                "Sec-Fetch-Mode: no-cors\r\n".
                                "Sec-Fetch-Site: same-site\r\n".
                                ($form_header ? $form_header."\r\n" : ''),
                        ],
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                        ],
                    ];

                    if ($proxy) {
                        $get_options['http']['proxy'] = $proxy;
                        //                    $get_options['http']['request_fulluri'] = true;

                        if ($proxy_auth) {
                            $get_options['http']['header'] .= "Proxy-Authorization: Basic $proxy_auth\r\nAuthorization: Basic $proxy_auth\r\n";
                        }

                        $this->logger->info("[7] Using proxy $proxyid $proxy ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);
                    }

                    $get_context = \stream_context_create($get_options);

                    if ($captcha_token && $token) {
                        if (isset($cookies[$captcha_token])) {
                            $token = $cookies[$captcha_token];
                        }

                        $captcha_url = $captcha_url.'?'.$captcha_token.'='.$token;
                    }

                    $captcha = \file_get_contents($captcha_url, false, $get_context);

                    $this->logger->info("Getting captcha: $captcha_url, size: ".\strlen($captcha), [
                        'cid' => Coroutine::getCid(),
                    ]);

                    $cookies = \array_merge($cookies, CookieUtilStatic::parse_cookies($http_response_header));

                    if ($captcha && \strlen($captcha) > 100) {
                        if (!\str_contains($captcha, '<html') || \strpos($captcha, '<html') > 30) {
                            $this->logger->info('Captcha loaded successfully', [
                                'cid' => Coroutine::getCid(),
                            ]);
                        } else {
                            $captcha = 'ERROR_NOT_IMAGE';

                            $this->logger->warning("Captcha not loaded - html received ($code)", [
                                'cid' => Coroutine::getCid(),
                            ]);
                        }
                    } elseif (
                        isset($http_response_header)
                        && \count($http_response_header) > 0
                        && false != \strpos($http_response_header[0], '302')
                    ) {
                        $captcha = 'ERROR_BAD_IMAGE';

                        foreach ($http_response_header as $line) {
                            if (\str_contains($line, 'Location:')) {
                                $captcha_path = \trim(\substr($line, 9));
                                $captcha_url = ('http' == \substr($captcha_path, 0, 4)
                                        ? ''
                                        : $server).$captcha_path;

                                $this->logger->info("Captcha redirect: $captcha_url ($code)");

                                $captcha = '';
                            }
                        }
                    } else {
                        $captcha = 'ERROR_ZERO_IMAGE';

                        $this->logger->info("Captcha not loaded - answer or redirect expected ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);
                    }

                    if ($proxy) {
                        $success = $captcha && ('ERROR' != \substr($captcha, 0, 5)) ? 1 : 0;

                        $this->connection->executeStatement(
                            "INSERT INTO proxyusage (sourceid,proxyid,success) VALUES ($sourceid, $proxyid, $success)"
                        );

                        if ($success) {
                            $this->connection->executeStatement(
                                "UPDATE proxy SET used=used+1,lasttime=now(),success=success+1,successtime=now() WHERE id=$proxyid"
                            );
                        } else {
                            $this->connection->executeStatement(
                                "UPDATE proxy SET used=used+1,lasttime=now() WHERE id=$proxyid",
                            );

                            $this->logger->warning("Proxy $proxyid $proxy failed ($code)", [
                                'cid' => Coroutine::getCid(),
                            ]);
                        }
                    }

                    if (
                        $captcha
                        && ('ERROR' != \substr($captcha, 0, 5))
                        && ('base64' == $row['captcha_format'])
                    ) {
                        if ($captcha_token_regexp) {
                            if (\preg_match($captcha_token_regexp, $captcha, $matches)) {
                                $token = $matches[1];

                                $this->logger->info("Token $token found ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            } else {
                                $this->logger->warning("Token $captcha_token_regexp not found ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);

                                $captcha = false;
                            }
                        }

                        $prefix = 'data:image/jpeg;base64,';
                        $start = \strpos($captcha, $prefix);

                        if (false !== $start) {
                            $captcha = \substr($captcha, $start + \strlen($prefix));
                            $finish = \strpos($captcha, '=');
                            if (false !== $finish) {
                                $captcha = \base64_decode(\substr($captcha, 0, $finish + 1));
                                $this->logger->info("Captcha decoded successfully ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            } else {
                                $captcha = 'ERROR_BASE64';
                                $this->logger->warning("Captcha decoding error ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            }
                        } else {
                            $captcha = 'ERROR_BASE64';
                            $this->logger->warning("Captcha decoding error ($code)", [
                                'cid' => Coroutine::getCid(),
                            ]);
                        }
                    }

                    if (
                        $captcha
                        && ('ERROR' != \substr($captcha, 0, 5))
                        && ('json' == $row['captcha_format'])
                    ) {
                        $json = \json_decode($captcha, true);

                        if ($token_field) {
                            if (\is_array($json) && isset($json[$token_field])) {
                                $token = $json[$token_field];
                                $this->logger->info("Token $token found ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            } else {
                                $this->logger->warning("Token $token_field not found ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                                //                            $this->logger->info($form);
                                $captcha = false;
                            }
                        }

                        if ($captcha_field) {
                            if (\is_array($json) && isset($json[$captcha_field])) {
                                $captcha = \base64_decode($json[$captcha_field]);
                                $this->logger->info("Captcha decoded successfully ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            } else {
                                $captcha = 'ERROR_JSON';
                                $this->logger->warning("Captcha decoding error ($code)", [
                                    'cid' => Coroutine::getCid(),
                                ]);
                            }
                        }
                    }
                }

                $cookies_str = \addslashes(CookieUtilStatic::cookies_str($cookies));

                if ('ERROR' == \substr((string) $captcha, 0, 5)) {
                    $captcha = false;
                    $captcha_format = false;
                }

                if ($captcha) {
                    $captcha_format = 'image';
                }

                if (($form || $login_post) && $captcha_format) {
                    if (
                        $this->connection->executeStatement(
                            "INSERT INTO session (sourceid,cookies,starttime,lasttime,sessionstatusid,captcha,token,server,sourceaccessid,proxyid) VALUES ($sourceid,'$cookies_str',now(),now(),1,'','$token','$server',".($sourceaccessid ?: 'NULL').','.($proxy ? "'".$proxyid."'" : 'NULL').')'
                        )
                    ) {
                        $sessionid = $this->connection->lastInsertId();

                        $this->logger->info("Created captcha session: $sessionid ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);

                        if ($captcha) {
                            $captcha_pic = "captcha/$code/__$sessionid.jpg";

                            $this->loggerUtil->log($captcha_pic, $captcha);
                            //                        file_put_contents("captcha/$code/$sessionid.htm",$http_response_header."\n\n".$form);
                        }
                    } else {
                        $this->logger->warning(
                            "Session insert failed ($code)\nINSERT INTO session (sourceid,cookies,starttime,lasttime,sessionstatusid,token) VALUES ($sourceid,'$cookies_str',now(),now(),1,'$token')",
                            [
                                'cid' => Coroutine::getCid(),
                            ]
                        );
                    }

                    if ($captcha && isset(NeuroUtil::NEURO_SOURCES[$code])) {
                        $key = '';
                        $host = 'neuro';
                        $antigateid = $this->neuroUtil->neuro_post(
                            $captcha,
                            NeuroUtil::NEURO_SOURCES[$code].'decode',
                        ); // передаем на распознавание
                    } elseif ($captcha) {
                        $key = $antigate_key;
                        $host = $antigate_host;
                        $antigateid = $this->antigateUtil->antigate_post(
                            $captcha,
                            $key,
                            false,
                            $host,
                            0,
                            (int) (2 == $captcha_type),
                            (int) (1 == $captcha_type),
                            $captcha_size,
                            $captcha_size ?: 99,
                            (int) (3 == $captcha_type),
                        ); // передаем на распознавание
                    } elseif ('hcaptcha' == $captcha_format) {
                        $key = $hcaptcha_key;
                        $host = $hcaptcha_host;
                        $antigateid = $this->captchaUtil->captcha_create(
                            $captcha_format,
                            false,
                            $captcha_token,
                            $form_url,
                            $captcha_action,
                            $captcha_minscore,
                            $key,
                            false,
                            $host,
                        ); // запрашиваем новый токен
                    } elseif ('v3' == $captcha_format) {
                        $key = $captchav3_key;
                        $host = $captchav3_host;
                        $antigateid = $this->captchaUtil->captcha_create(
                            $captcha_format,
                            false,
                            $captcha_token,
                            $form_url,
                            $captcha_action,
                            $captcha_minscore,
                            $key,
                            false,
                            $host,
                        ); // запрашиваем новый токен
                    } else {
                        $key = $captcha_key;
                        $host = $captcha_host;
                        $antigateid = $this->captchaUtil->captcha_create(
                            $captcha_format,
                            false,
                            $captcha_token,
                            $form_url,
                            $captcha_action,
                            $captcha_minscore,
                            $key,
                            false,
                            $host,
                        ); // запрашиваем новый токен
                    }

                    if ($antigateid && (!\str_contains((string) $antigateid, 'ERROR'))) {
                        $sessions[$sessionid] = [
                            'sourceid' => $sourceid,
                            'code' => $code,
                            'captcha_format' => $captcha_format,
                            'captcha_type' => $captcha_type,
                            'captcha_size' => $captcha_size,
                            'cookies' => $cookies,
                            'antigatehost' => $host,
                            'antigatekey' => $key,
                            'antigateid' => $antigateid,
                            'starttime' => \microtime(true),
                            'method' => $captcha_check_method,
                            'url' => $captcha_check_url,
                            'params' => $params,
                            'field' => $captcha_field,
                            'token_field' => $token_field,
                            'token' => $token,
                            'token_regexp' => $captcha_check_token_regexp,
                            'proxy' => $proxy,
                            'proxy_auth' => $proxy_auth,
                        ];

                        $this->connection->executeStatement(
                            "UPDATE session SET captcha_service='".$host."'".('neuro' != $host ? ",captcha_id=$antigateid" : '')." WHERE id=$sessionid"
                        );

                        $this->logger->info('Captcha id from '.$host." - $antigateid ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);
                    } else {
                        $this->logger->warning('Failed sending captcha to '.$host." - $antigateid ($code)", [
                            'cid' => Coroutine::getCid(),
                        ]);

                        if ($captcha && isset(NeuroUtil::NEURO_SOURCES[$code])) {
                            $key = $antigate_key;
                            $host = $antigate_host;
                            $antigateid = $this->antigateUtil->antigate_post(
                                $captcha,
                                $key,
                                false,
                                $host,
                                0,
                                (int) (2 == $captcha_type),
                                (int) (1 == $captcha_type),
                                $captcha_size,
                                $captcha_size ?: 99,
                                (int) (3 == $captcha_type),
                            ); // передаем на распознавание
                        } elseif ($captcha) {
                            $key = $antigate_key2;
                            $host = $antigate_host2;
                            $antigateid = $this->antigateUtil->antigate_post(
                                $captcha,
                                $key,
                                false,
                                $host,
                                0,
                                (int) (2 == $captcha_type),
                                (int) (1 == $captcha_type),
                                $captcha_size,
                                $captcha_size ?: 99,
                                (int) (3 == $captcha_type),
                            ); // передаем на распознавание
                        } elseif ('hcaptcha' == $captcha_format) {
                            $key = $hcaptcha_key2;
                            $host = $hcaptcha_host2;
                            $antigateid = $this->captchaUtil->captcha_create(
                                $captcha_format,
                                false,
                                $captcha_token,
                                $form_url,
                                $captcha_action,
                                $captcha_minscore,
                                $key,
                                false,
                                $host,
                            ); // запрашиваем новый токен
                        } else {
                            $key = $captcha_key2;
                            $host = $captcha_host2;
                            $antigateid = $this->captchaUtil->captcha_create(
                                $captcha_format,
                                false,
                                $captcha_token,
                                $form_url,
                                $captcha_action,
                                $captcha_minscore,
                                $key,
                                false,
                                $host,
                            ); // запрашиваем новый токен
                        }

                        if ($antigateid && (!\str_contains($antigateid, 'ERROR'))) {
                            $sessions[$sessionid] = [
                                'sourceid' => $sourceid,
                                'code' => $code,
                                'captcha_format' => $captcha_format,
                                'captcha_type' => $captcha_type,
                                'captcha_size' => $captcha_size,
                                'cookies' => $cookies,
                                'antigatehost' => $host,
                                'antigatekey' => $key,
                                'antigateid' => $antigateid,
                                'starttime' => \microtime(true),
                                'method' => $captcha_check_method,
                                'url' => $captcha_check_url,
                                'params' => $params,
                                'field' => $captcha_field,
                                'token_field' => $token_field,
                                'token' => $token,
                                'token_regexp' => $captcha_check_token_regexp,
                                'proxy' => $proxy,
                                'proxy_auth' => $proxy_auth,
                            ];

                            $this->connection->executeStatement(
                                "UPDATE session SET captcha_service='".$host."',captcha_id=$antigateid WHERE id=$sessionid"
                            );

                            $this->logger->info('Captcha id from '.$host." - $antigateid ($code)", [
                                'cid' => Coroutine::getCid(),
                            ]);
                        } else {
                            $this->logger->warning('Failed sending captcha to '.$host." - $antigateid ($code)", [
                                'cid' => Coroutine::getCid(),
                            ]);
                            $this->connection->executeStatement(
                                "UPDATE session SET sessionstatusid=4,statuscode='failedcaptcha',endtime=now() WHERE id=$sessionid AND sessionstatusid=1"
                            );
                        }
                    }
                }

                if (($login_post_path || $form) && !$captcha_url && !$captcha_format && (!$auth_path_regexp || $auth_path)) {
                    $this->connection->executeStatement(
                        "INSERT INTO session (sourceid,cookies,starttime,lasttime,sessionstatusid,captcha,token,server,sourceaccessid,proxyid) VALUES ($sourceid,'$cookies_str',now(),now(),2,'','$token','$server',".($sourceaccessid ?: 'NULL').','.($proxy ? "'".$proxyid."'" : 'NULL').')'
                    );
                    $sessionid = $this->connection->lastInsertId();
                    if ($sessionid) {
                        $this->logger->info(
                            "Created session: $sessionid ($code".($login ? " login $login" : '').')', [
                                'cid' => Coroutine::getCid(),
                            ]
                        );
                    //                    if ($sourceaccessid)
                    //                        db_execute($db,"UPDATE sourceaccess SET unlocktime=NULL WHERE sourceaccessid=$sourceaccessid");
                    } else {
                        $this->logger->warning(
                            "Session insert failed ($code login $login)\nINSERT INTO session (sourceid,cookies,starttime,lasttime,sessionstatusid,captcha,server,sourceaccessid,proxyid) VALUES ($sourceid,'$cookies_str',now(),now(),2,'','$server',".($sourceaccessid ?: 'NULL').','.($proxy ? "'".$proxyid."'" : 'NULL').')',
                            [
                                'cid' => Coroutine::getCid(),
                            ]
                        );
                    }
                }
            }
        }

        return $res;
    }
}
