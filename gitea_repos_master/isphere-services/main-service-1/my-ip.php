<?php

header('Sec-CH-IP-Geo: '.$_SERVER['HTTP_SEC_CH_IP_GEO'] ?? '');

echo $_SERVER['REMOTE_ADDR'], PHP_EOL;
