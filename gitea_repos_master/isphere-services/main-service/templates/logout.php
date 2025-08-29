<?php

include 'config.php';
include 'auth.php';

$user->getUserIdentifier() = '';
$user->getPassword() = '';
$_SESSION['userid'] = 0;

echo 'Спасибо, что воспользовались нашим сервисом. До свидания!<br/><br/><a href="admin.php">Войти снова</a>';

include 'footer.php';
