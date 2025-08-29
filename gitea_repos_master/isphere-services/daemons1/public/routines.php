<?php

require_once('str.php');
require_once('xml.php');
require_once('db.php');
require_once('cookies.php');
require_once('antigate.php');
require_once('captcha.php');
require_once('neuro.php');

function print_log($msg) {
  $line = date('Y-m-d H:i:s')." ".getmypid()." $msg\r\n";
  echo $line;
}

?>
