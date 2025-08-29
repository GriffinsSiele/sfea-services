<?php

// База данных
$database = [
    'server' => 'localhost',
    'login' => 'isphere',
    'password' => 'KgRh85s-FJz91.o4$',
    'name' => 'isphere',
];

$dbstat = [
    'server' => '172.16.11.1',
    'login' => 'adminer',
    'password' => 'am2Jethu5Haepe7sae',
    'name' => 'isphere',
];

// Рабочий каталог
$workpath = '/var/www/html/2.00/';

// Путь к протоколам
$logpath = $workpath.'logs/';

// Путь к xml
$xmlpath = '/opt/xml/';

// URL сервиса
// $serviceurl = 'https://i-sphere.ru/2.00/';
$serviceurl = request_url(1);

// Параметры HTTP
$http_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:106.0) Gecko/20100101 Firefox/106.0';
$http_connecttimeout = 5;
$http_timeout = 55;
$total_timeout = 60;
$form_timeout = 60;

$servicenames = [
    'i-sphere.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
    'www.i-sphere.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
    'my.i-sphere.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
    'infosfera.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
    'www.infosfera.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
    'lk.infosfera.ru' => '<a href="https://инфосфера.рус" target="_blank">Инфосфера</a>',
    'my.infohub24.ru' => '<a href="http://infohub24.ru" target="_blank">Инфохаб</a>',
];

function request_url($trims)
{
    $result = '';
    $default_port = 80;
    if (isset($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
        $result .= 'https://';
        $default_port = 443;
    } else {
        $result .= 'http://';
    }
    //    $result .= $_SERVER['SERVER_NAME'];
    $result .= isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];

    if ($_SERVER['SERVER_PORT'] != $default_port) {
        $result .= ':'.$_SERVER['SERVER_PORT'];
    }
    $uri = $_SERVER['REQUEST_URI'];
    if ($trims) {
        $pos = \strrpos($uri, '/');
        if (false === $pos) {
            $pos = 0;
        } else {
            ++$pos;
        }
        $uri = \substr($uri, 0, $pos);
    }
    $result .= $uri;

    return $result;
}
