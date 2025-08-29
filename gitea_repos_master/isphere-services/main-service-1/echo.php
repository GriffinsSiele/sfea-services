<?php

if (isset($_GET['utm_source'])) {
	echo 'isphere_callback("', addslashes(json_encode([
		'client_ip' => $_SERVER['REMOTE_ADDR'],
		'utm_source' => $_GET['utm_source'],
	])), '")';
	exit;
}

//include('config.php');
echo 'OK';

