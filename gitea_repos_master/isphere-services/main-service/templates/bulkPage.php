<?php

include 'config.php';
include 'auth.php';

$condition = '';
$userid = get_user_id($mysqli);
$user_level = get_user_level($mysqli);
$user_area = get_user_area($mysqli);
$user_access = get_user_access($mysqli);

if (!$user_access['bulk']) {
    echo 'У вас нет доступа к этой странице';
    return;
}

if ($user_area <= 2) {
    $condition .= " AND (user_id=$userid";
    if ($user_area >= 1) {
        $condition .= " OR user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId=$userid)";
        if ($user_area > 1) {
            $condition .= " OR user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId IN (SELECT id FROM SystemUsers WHERE MasterUserId=$userid))";
        }
    }
    $condition .= ')';
}

$id = (isset($_REQUEST['bulkId']) && \preg_match("/^[1-9]\d+$/", $_REQUEST['bulkId'])) ? $_REQUEST['bulkId'] : '';
if (!$id) {
    $id = 0;
}

$select = "SELECT * FROM isphere.Bulk WHERE id=$id $condition LIMIT 1";

$sqlRes = $mysqli->query($select);
if ($sqlRes->num_rows > 0) {
    $result = $sqlRes->fetch_assoc();
} else {
    echo "Данные реестра $id недоступны";
    return;
}
$sqlRes->close();

$titles = [0 => 'Добавлен', 10 => 'Предобработка', 20 => 'Добавление в очередь', 30 => 'Проверка результатов', 40 => 'Генерация отчета'];

echo '<html><head>';
echo '<link rel="stylesheet" type="text/css" href="public/main.css"/>';
//      echo '<meta http-equiv="Refresh" content="30"/></head>';
echo '<h1>'.$result['filename'].'</h1><hr/><a href="bulk.php">К списку реестров</a><br/><br/>';
//      echo '<a href="/2.00/bulkAuto/stepOne.php" target=_blank>Загрузить новый реестр</a><br /><br />';
//      echo '<pre>';
//      print_r($result);
//      echo '</pre>';
if (\file_exists('/opt/bulk/'.$id.'/status.txt')) {
    $status = \file_get_contents('/opt/bulk/'.$id.'/status.txt');

    echo '<b>Файл '.$result['filename'].' ('.$result['total_rows'].' строк) отправлен на пакетную обработку по источникам '.$result['sources'].'</b><br />';
    echo 'Статус: '.$titles[$status].'<br />Выполняется обработка.<br />';
    echo '<a href="break.php?bulkId='.$id.'">Прервать обработку (навсегда)</a><br />';
} elseif (1 == $result['status']) {
    echo 'Обработка окончена! <a href="bulkresult.php?id='.$id.'">Скачать результаты</a><br /><br />';
} elseif (2 == $result['status']) {
    echo 'Реестр в ожидании обработки<br /><br />';
} elseif (3 == $result['status']) {
    echo 'Обработка реестра прервана<br /><br />';
}

if (!isset($status) || $status > 10) {
    $sources = \explode(',', $result['sources']);
    foreach ($sources as $source) {
        echo '<br />Источник: '.$source.'<br />';
        $files = \glob('/opt/bulk/'.$id.'/'.$source.'/*/fResult.txt');
        echo 'Обработано: '.\count($files).'<br />';
        $errors = '';
        foreach ($files as $file) {
            $fileContent = \file_get_contents($file);
            if (\preg_match('/<Error>([^<>]+)/si', $fileContent, $matches)) {
                $tmp = \explode('/', $file);
                $incFile = \strtr($file, ['.txt' => '.inc']);
                $inc = \file($incFile) ? \file_get_contents($incFile) : 0;
                $errors .= 'Строка '.$tmp[5].': '.$matches[0].($inc ? ' ('.$inc.' попыток)' : '').'<br />';
            }
        }
        if ('' != $errors) {
            echo 'Ошибки:<br />';
            echo $errors;
        }
    }
}

include 'footer.php';
