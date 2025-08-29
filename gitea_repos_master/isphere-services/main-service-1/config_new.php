<?php

require_once __DIR__ . '/vendor/autoload.php';

ini_set("display_errors", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
error_reporting(E_ALL ^ E_DEPRECATED);

// База данных
$database = array(
	'server' => getenv('DATABASE_SERVER'),
	'login' => getenv('DATABASE_LOGIN'),
	'password' => getenv('DATABASE_PASSWORD'),
	'name' => getenv('DATABASE_NAME'),
	'connect_timeout' => intval(getenv('DATABASE_CONNECT_TIMEOUT'), 10),
	'read_timeout' => intval(getenv('DATABASE_READ_TIMEOUT'), 10),
);

$dbstat = array(
	'server' => getenv('DATABASE_STAT_SERVER'),
	'login' => getenv('DATABASE_STAT_LOGIN'),
	'password' => getenv('DATABASE_STAT_PASSWORD'),
	'name' => getenv('DATABASE_STAT_NAME'),
	'connect_timeout' => intval(getenv('DATABASE_STAT_CONNECT_TIMEOUT'), 10),
	'read_timeout' => intval(getenv('DATABASE_STAT_READ_TIMEOUT'), 10),
);

// Ответы источников
$keydb = array(
	'server' => '172.16.0.3', //getenv('KEYDB_SERVER_0'),
	'server1' => getenv('KEYDB_SERVER_1'),
	'server2' => getenv('KEYDB_SERVER_2'),
	'auth' => [getenv('KEYDB_PASSWORD_0')],
	'connect_timeout' => intval(getenv('KEYDB_CONNECT_TIMEOUT'), 10),
	'read_timeout' => intval(getenv('KEYDB_READ_TIMEOUT'), 10),
	'tries' => 10,
	'try_interval' => 3,
);

$rabbitmq = array(
	'host' => '172.16.0.11', //getenv('RABBITMQ_HOST'),
	'host1' => getenv('RABBITMQ_HOST'),
	'port' => intval(getenv('RABBITMQ_PORT'), 10),
	'vhost' => getenv('RABBITMQ_VHOST'),
	'login' => getenv('RABBITMQ_LOGIN'),
	'password' => getenv('RABBITMQ_PASSWORD'),
	'connect_timeout' => 3,
	'read_timeout' => 3,
	'write_timeout' => 3,
	'rpc_timeout' => 3,
	'tries' => 10,
	'try_interval' => 3,
);

$proxphere = false; // '172.16.100.1'; //getenv('PROXPHERE');

// Рабочий каталог
$workpath = '/var/www/html/2.00/';

// Путь к протоколам
$logpath = $workpath . 'logs/';

// Путь к xml
$xmlpath = '/opt/xml/';

// URL сервиса
$serviceurl = isset($_SERVER['REQUEST_URI'])?request_url(1):'https://i-sphere.ru/2.00/';

// Параметры HTTP
$http_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/114.0';
$http_connecttimeout = 5;
$http_timeout = 55;
$total_timeout = 180;
$form_timeout = 180;

$servicenames = array(
    'i-sphere.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
    'www.i-sphere.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
    'my.i-sphere.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
    'infosfera.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
    'www.infosfera.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
    'lk.infosfera.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
    'my.infohub24.ru' => '<a href="http://infohub24.ru" target="_blank">Инфохаб</a>',
);

// sentry configuration {{{
Sentry\init([
	'dsn' => 'http://e6c830a59342438787ab9cdf9505ae9e@172.16.97.10:8010/2',
]);
// sentry configuration }}}

function request_url($trims)
{
    $result = '';
    $default_port = 80; 
    if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS']=='on')) {
        $result .= 'https://';
        $default_port = 443;
    } else {
        $result .= 'http://';
    }
//    $result .= $_SERVER['SERVER_NAME'];
    $result .= isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME'];
 
    if ($_SERVER['SERVER_PORT'] != $default_port) {
        $result .= ':'.$_SERVER['SERVER_PORT'];
    }
    $uri = $_SERVER['REQUEST_URI'];
    if ($trims) {
        $pos = strrpos($uri,'/');
        if ($pos===false)
            $pos = 0;
        else
            $pos++;
        $uri = substr($uri,0,$pos);
    }
    $result .= $uri;
    return $result;
}

?>
