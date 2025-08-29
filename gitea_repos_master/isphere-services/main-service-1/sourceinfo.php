<?php

include('config.php');
include('auth.php');

$user_access = get_user_access($mysqli);
if (!$user_access['sources']) {
    echo 'У вас нет доступа к этой странице';
    exit;
}
$user_sources = get_user_sources($mysqli);

$db = mysqli_connect ($database['server'],$database['login'],$database['password'], $database['name']) or die(mysqli_errno($db) . ": " . mysqli_error($db));
if ($db) {
    mysqli_query($db, "Set character set utf8");
    mysqli_query($db, "Set names 'utf8'");
}

    echo '<link rel="stylesheet" type="text/css" href="main.css"/>';

    $code = '';
    if(isset($_REQUEST['checktype']) && $_REQUEST['checktype'] && preg_match("/^[a-z0-9_]+$/",$_REQUEST['checktype'])){
        $code = $_REQUEST['checktype'];
    }

    $objlist = array(
        'person' => 'физлицо',
        'org' => 'организация',
        'phone' => 'телефон',
        'email' => 'e-mail',
        'url' => 'ссылка',
        'auto' => 'автомобиль',
        'ip' => 'ip-адрес',
        'nick' => 'псевдоним',
    );
    $found = false;
    $response = mysqli_query($db, "SELECT * FROM isphere.CheckType WHERE code='$code'");
    if($row = $response->fetch_assoc()){
        if ((isset($user_sources[$row['code']]) && $user_sources[$row['code']]) || (isset($user_sources[$row['source_code']]) && $user_sources[$row['source_code']])){
            $found = true;
            echo "<h1>{$row['title']}</h1>\n<hr/><a href=\"sources.php\">Назад</a><br/><br/>\n";
            echo "<b>Код проверки</b>: {$row['code']}<br/>\n";
            echo "<b>Код источника</b>: {$row['source_code']}<br/>\n";
            echo "<b>Источник</b>: {$row['source_name']}<br/>\n";
            echo "<b>Объекты проверки</b>: ";
            $obj = array();
            foreach ($objlist as $objcode => $objdesc)
               if (isset($row[$objcode]) && $row[$objcode]) $obj[] = $objdesc;
            echo implode(',',$obj)."<br/>\n";
        }
    }
    if (!$found) {
        echo 'Информация отсутствует или недоступна';
        exit;
    }

    echo "<b>Возможные поля ответа</b>: <br/>\n";
    $response = mysqli_query($db, "SELECT * FROM isphere.Field WHERE checktype='$code'");
    echo "<table border=1><tr><th>Имя</th><th>Описание</th><th>Тип</th></tr>\n";
    while($row = $response->fetch_assoc()){
        echo "<tr><td>{$row['name']}</td><td>{$row['description']}</td><td>{$row['type']}</td></tr>\n";
    }
    echo "</table><br/>\n";


$db->close();

include('footer.php');
