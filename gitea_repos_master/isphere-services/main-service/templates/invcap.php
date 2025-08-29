<?php

include 'config.php';
include 'auth.php';

//      $mysqli = mysqli_connect ($database['server'],$database['login'],$database['password'], $database['name']);

echo '<link rel="stylesheet" type="text/css" href="public/main.css"/>';
echo '<h1>Неверные капчи</h1><hr/><a href="admin.php">Назад</a><br/><br/>';

$userid = get_user_id($mysqli);
$user_level = get_user_level($mysqli);
$user_area = get_user_area($mysqli);
$conditions = '';
$users = '';
$users_list = '';

$select = 'SELECT Id, Login, Locked FROM isphere.SystemUsers';
if ($user_area <= 2) {
    $select .= " WHERE Id=$userid";
    if ($user_area >= 1) {
        $select .= "  OR MasterUserId=$userid";
        if ($user_area > 1) {
            $select .= " OR MasterUserId IN (SELECT id FROM SystemUsers WHERE MasterUserId=$userid)";
        }
    } else {
        $_REQUEST['user_id'] = $userid;
    }
}
$select .= ' ORDER BY Login';
$sqlRes = $mysqli->query($select);
if ($sqlRes->num_rows > 1) {
    while ($result = $sqlRes->fetch_assoc()) {
        $users_list .= ($users_list ? ',' : '').$result['Id'];
    }
    if ($user_area <= 2) {
        $conditions .= ' AND user_id IN ('.$users_list.')';
    }
}
$sqlRes->close();

if (isset($_REQUEST['action']) && isset($_REQUEST['id'])) {
    if ('delete' == $_REQUEST['action']) {
        $mysqli->query('DELETE FROM session WHERE id='.(int) $_REQUEST['id']);
    } elseif ('report' == $_REQUEST['action'] && isset($_REQUEST['captcha_id'])) {
        $apikey = 'd167c71a9278312f184f17caa4e71050'; // rucaptcha
        $ch = \curl_init();
        \curl_setopt($ch, \CURLOPT_URL, "http://rucaptcha.com/res.php?key=$apikey&action=reportbad&id=".(int) $_REQUEST['captcha_id']);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, 1);
        $answer = \curl_exec($ch);
        \curl_close($ch);
        if ('OK_REPORT_RECORDED' == $answer) {
            $mysqli->query('UPDATE session SET captcha_reporttime=now() WHERE id='.(int) $_REQUEST['id']);
        }
    }
}

$hours = isset($_REQUEST['hours']) && \preg_match('/^[0-9]{1,2}$/', $_REQUEST['hours']) ? $_REQUEST['hours'] : '1';
$fromtime = 'date_sub(now(),interval '.$hours.' hour)';

echo '<form action="">';
echo '<select name="hours">';
echo '<option value="1"'.('1' == $hours ? ' selected' : '').'>За последний час</option>';
echo '<option value="3"'.('3' == $hours ? ' selected' : '').'>За 3 часа</option>';
echo '<option value="6"'.('6' == $hours ? ' selected' : '').'>За 6 часов</option>';
echo '<option value="12"'.('12' == $hours ? ' selected' : '').'>За 12 часов</option>';
echo '<option value="24"'.('24' == $hours ? ' selected' : '').'>За 24 часа</option>';
echo '<option value="72"'.('72' == $hours ? ' selected' : '').'>За 3 суток</option>';
echo '</select>';
echo ' <input type="submit" value="Обновить"></form>';

$sql = <<<SQL
SELECT s.id,s.starttime,s.endtime,timediff(s.endtime,s.starttime) lifetime,source.code,s.captcha_service,s.captcha_id,s.captcha,s.captcha_reporttime FROM session s, source
WHERE s.sourceid=source.id AND s.sessionstatusid=4 AND s.captcha>'' AND s.starttime >= $fromtime ORDER BY 1 DESC
SQL;

$title = [
    'id' => 'ID сессии',
    'starttime' => 'Создано',
    'endtime' => 'Завершено',
    'lifetime' => 'Длительность',
    'code' => 'Источник',
    'captcha_service' => 'Сервис',
    'captcha_id' => 'ID капчи',
//          "captcha_reporttime" => "Отправлен отчет",
    'captcha' => 'Распознано',
    'image' => 'Изображение',
];

$i = 0;
$sqlRes = $mysqli->query($sql);

echo "<table border=1>\n";
while ($result = $sqlRes->fetch_assoc()) {
    //              print_r($result);
    if (0 == $i) {
        $first = $result;
        echo "<tr>\n";
        foreach ($result as $key => $val) {
            if (isset($title[$key])) {
                echo '<th>'.$title[$key].'</th>';
            }
        }
        echo '<th>'.$title['image'].'</th>';
        echo '<th>Действия</th>';
        echo "</tr>\n";
    }
    echo "<tr>\n";
    foreach ($result as $key => $val) {
        if (isset($title[$key])) {
            echo '<td>'.(\strlen($val) <= 32 ? $val : '').'</td>';
        }
    }
    echo '<td>'.(\file_exists("captcha/{$result['code']}/{$result['id']}.jpg") ? "<img src=\"captcha/{$result['code']}/{$result['id']}.jpg\"/>" : '').'</td>';
    echo "<td><a href=\"invcap.php?action=delete&id={$result['id']}\">Удалить</a><br/>";
    if ('rucaptcha.com' == $result['captcha_service'] && !$result['captcha_reporttime'] && 'ERROR_' != \substr($result['captcha'], 0, 6)) {
        echo "<a href=\"invcap.php?action=report&id={$result['id']}&captcha_id={$result['captcha_id']}\">Отчет</a>";
    }
    echo "</td></tr>\n";
    ++$i;
}
if (0 == $i) {
    echo 'Нет данных';
}
$sqlRes->close();
echo "</table><br />\n";

$mysqli->close();

include 'footer.php';
