<?php

include('config.php');
include('auth.php');

$user_level = get_user_level($mysqli);
$user_access = get_user_access($mysqli);

echo '<link rel="stylesheet" type="text/css" href="main.css"/>';
$user_message = get_user_message($mysqli);
if ($user_message) {
    echo '<span class="message">'.$user_message.'</span><hr/>';
}

echo '<h1>Поиск в общедоступных источниках</h1><hr/>';
echo $user_access['check']?'<a href="check.php">Проверка физ.лица</a><br/>':'';
echo $user_access['checkorg']?'<a href="checkorg.php">Проверка организации</a><br/>':'';
echo $user_access['checkphone']?'<a href="checkphone.php">Проверка телефона</a><br/>':'';
echo $user_access['checkphone_by']?'<a href="checkphone_by.php">Проверка телефона (Беларусь)</a><br/>':'';
echo $user_access['checkphone_by']?'<a href="checkphone_ua.php">Проверка телефона (Украина)</a><br/>':'';
echo $user_access['checkphone_kz']?'<a href="checkphone_kz.php">Проверка телефона (Казахстан)</a><br/>':'';
echo $user_access['checkphone_uz']?'<a href="checkphone_uz.php">Проверка телефона (Узбекистан)</a><br/>':'';
echo $user_access['checkphone_bg']?'<a href="checkphone_bg.php">Проверка телефона (Болгария)</a><br/>':'';
echo $user_access['checkphone_ro']?'<a href="checkphone_ro.php">Проверка телефона (Румыния)</a><br/>':'';
echo $user_access['checkphone_pl']?'<a href="checkphone_pl.php">Проверка телефона (Польша)</a><br/>':'';
echo $user_access['checkphone_pt']?'<a href="checkphone_pt.php">Проверка телефона (Португалия)</a><br/>':'';
echo $user_access['checkemail']?'<a href="checkemail.php">Проверка e-mail</a><br/>':'';
echo $user_access['checkurl']?'<a href="checkurl.php">Проверка профиля соцсети</a><br/>':'';
echo $user_access['checkskype']?'<a href="checknick.php">Проверка псевдонима (логина/ника)</a><br/>':'';
echo $user_access['checkauto']?'<a href="checkauto.php">Проверка автомобиля</a><br/>':'';
echo $user_access['checkip']?'<a href="checkip.php">Проверка ip-адреса</a><br/>':'';
//echo $user_access['checkcard']?'<a href="checkcard.php">Проверка карты</a><br/>':'';
echo $user_access['chey']?'<a href="cheytelefon.php">Чей телефон</a><br/>':'';
echo '<hr/>';
echo $user_access['bulk']?'<a href="bulk.php">Обработка реестра</a><br/>':'';
echo '<hr/>';
echo $user_access['history']?'<a href="history.php">История запросов</a><br/>':'';
//echo $user_access['users']?'<a href="users.php">Пользователи</a><br/>':'';
echo $user_access['reports']?'<a href="reports.php">Статистика</a><br/>':'';
echo '<hr/>';
echo $user_access['news']?'<a href="news.php">Новости платформы</a><br/>':'';
echo $user_access['sources']?'<a href="sources.php">Список источников</a><br/>':'';
echo '<hr/>';
echo '<a href="logout.php">Выйти</a><br/>';

include('footer.php');
