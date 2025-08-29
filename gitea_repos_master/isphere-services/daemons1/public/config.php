<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require __DIR__ . '/vendor/autoload.php';

// sentry configuration {{{
Sentry\init([
    'dsn' => 'http://7b0eb8bf0352460593e3d02aba059c6a@glitchtip.i-sphere.local/23',
]);
// sentry configuration }}}

// База данных
$database = array(
    'server' => getenv('DATABASE_SERVER'),
    'login' => getenv('DATABASE_LOGIN'),
    'password' => getenv('DATABASE_PASSWORD'),
    'name' => getenv('DATABASE_NAME'),
);

$captcha_services = getenvlist('CAPTCHA_SERVICES_', 'HOST', 'SECRET_KEY');

// Параметры подключения к сервису распознавания картинок
$antigate_host = getenv('ANTIGATE_HOST_0');
$antigate_key = $captcha_services[$antigate_host];
$antigate_host2 = getenv('ANTIGATE_HOST_1');
$antigate_key2 = $captcha_services[$antigate_host2];

// Параметры подключения к сервису распознавания recaptcha
//$ip1 = rand(3,4); $ip2 = 7-$ip1;
//$captcha_host = '10.8.0.'.$ip1;
//$captcha_host = '172.16.12.10';
//$captcha_host = 'rucaptcha.com';
//$captcha_host = 'api.anti-captcha.com';
$captcha_host = getenv('CAPTCHA_HOST_0');
$captcha_key = $captcha_services[$captcha_host];

//$captcha_host2 = '10.8.0.'.$ip2;
//$captcha_host2 = 'api.capmonster.cloud';
//$captcha_host2 = 'api.anti-captcha.com';
$captcha_host2 = getenv('CAPTCHA_HOST_1');
$captcha_key2 = $captcha_services[$captcha_host2];

// Параметры подключения к сервису распознавания recaptcha v3
//$captchav3_host = 'api.capmonster.cloud';
$captchav3_host = getenv('CAPTCHA_V3_HOST_0');
$captchav3_key = $captcha_services[$captchav3_host];
//$captchav3_host2 = 'rucaptcha.com';
$captchav3_host2 = getenv('CAPTCHA_V3_HOST_1');
$captchav3_key2 = $captcha_services[$captchav3_host2];

// Параметры подключения к сервису распознавания hcaptcha
$hcaptcha_host = getenv('H_CAPTCHA_HOST_0');
//$hcaptcha_host = 'api.anti-captcha.com';
$hcaptcha_key = $captcha_services[$hcaptcha_host];
$hcaptcha_host2 = getenv('H_CAPTCHA_HOST_1');
//$hcaptcha_host2 = 'api.anti-captcha.com';
$hcaptcha_key2 = $captcha_services[$hcaptcha_host2];

$captcha_timeout = intval(getenv('CAPTCHA_TIMEOUT'), 10);

// Таймаут ожидания
$idle_time = intval(getenv('IDLE_TIME'), 10);

// Параметры HTTP
$http_agent = getenv('HTTP_USER_AGENT');
$http_timeout = intval(getenv('HTTP_TIMEOUT'), 10);

// Имя лог-файла
$log_file = getenv('LOG_FILE');

// Максимальное кол-во сессий для источника за один раз
$max_sessions = intval(getenv('MAX_SESSIONS'), 10);

// Максимальное время на один источник
$max_seconds = intval(getenv('MAX_SECONDS'), 10);

function getenvlist($prefix, $secretKey, $secretValue)
{
    $secretParamsValues = array_filter(getenv(), function ($key) use ($prefix) {
        return 0 === strpos($key, $prefix);
    }, ARRAY_FILTER_USE_KEY);

    /** @var array<array<string, string>> */
    $captchaServicesList = [];

    foreach ($secretParamsValues as $key => $value) {
        $secretParamIndex = 0;
        $secretParamKey = '';

        sscanf($key, $prefix . '%d_%s', $secretParamIndex, $secretParamKey);

        $captchaServicesList[$secretParamIndex][$secretParamKey] = $value;
    }

    /** @var array<string, string> */
    $captchaServices = [];

    foreach ($captchaServicesList as $item) {
        $captchaServices[$item[$secretKey]] = $item[$secretValue];
    }

    return $captchaServices;
}

$logger = new Logger('app');
$logger->pushHandler(new StreamHandler($log_file, Logger::DEBUG));
