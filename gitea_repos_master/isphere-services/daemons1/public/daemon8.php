#!/usr/bin/php -q
<?php
require('config.php');
require_once('routines.php');

$daemonnum = 8;
$sessiontable = "session";
$sourcefilter = "";
$proxyfilter = " AND mod(id,10)+1=$daemonnum";
$sourceaccessfilter = " AND mod(sourceaccessid,10)+1=0";
$sessionfilter = " AND mod(session.proxyid,10)+1=$daemonnum";

// Проверка на повторный запуск
$flock = fopen("daemon$daemonnum.lock", "w"); 
if (!($flock && flock($flock,LOCK_EX|LOCK_NB))) {
    die("Daemon already started\r\n");   
};

$daemonstarttime = microtime(true);
set_time_limit(600);
print_log("Daemon started");

include('daemon_inc.php');

print_log("Daemon stopped");
file_put_contents("CYCLE$daemonnum.txt", (intval(file_get_contents("CYCLE$daemonnum.txt")) + 1));
fclose($flock);
sleep(5);
