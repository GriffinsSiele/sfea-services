<?php

include('config.php');
include('auth.php');

$_SERVER['PHP_AUTH_USER'] = '';
$_SERVER['PHP_AUTH_PW'] = '';
$_SESSION['userid'] = 0;

echo 'Спасибо, что воспользовались нашим сервисом. До свидания!<br/><br/><a href="admin.php">Войти снова</a>';

include('footer.php');
