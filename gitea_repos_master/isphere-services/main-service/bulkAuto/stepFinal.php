<?php

include 'config.php';
include '../config.php';
include '../auth.php';
require 'functions.php';

$user_access = get_user_access($mysqli);

if (!$user_access['bulk']) {
    echo \json_encode(['error' => 'У вас нет доступа к загрузке файлов!']);
    exit;
}

$user_sources = get_user_sources($mysqli);

if (!\in_array('fssp', $user_sources) && !\in_array('fns', $user_sources) && !\in_array('bankrot', $user_sources) && !\in_array('notariat', $user_sources) && !\in_array('whatsapp', $user_sources)) {
    echo \json_encode(['error' => 'У вас нет доступа к источникам для автоматической обработки реестров!']);
    exit;
}

// print_r($_REQUEST);
// echo "<br>";
// exit;

$login = $_SERVER['PHP_AUTH_USER'];

$fileName = $_REQUEST['filename'];
$workDir = '/opt/upload/'.$login.'_'.$_REQUEST['workFile'].'/';
$ext = \pathinfo($fileName, \PATHINFO_EXTENSION);

// echo '<br>'.$workDir.$fileName.'<br>';
if (!\file_exists($workDir.'request.'.$ext)) {
    echo 'Вы пытаетесь заказать обработку несуществующего реестра!!';
    exit;
}

$fields = [];
foreach ($_REQUEST as $key => $val) {
    if (\preg_match("/^\d+$/", $key)) {
        $fields[$val] = $key;
    }
}

$sources = [];

// Array ( [workFile] => 1615211483 [filename] => 1614698310005.xls [0] => id [1] => fio [2] => bDate [sources] => Array ( [fssp] => on ) )

foreach ($_REQUEST['sources'] as $source => $val) {
    if (checkSource($source, $fields) && isset($user_sources[$source]) && 1 == $user_sources[$source]) {
        $sources[] = $source;
    }
}

// print_r($available);

if (!\count($sources)) {
    echo 'Не выбраны источники для обработки!!';
    exit;
}

$rows = \explode("\n", \file_get_contents($workDir.'pre.csv'));

$userid = get_user_id($mysqli);
$mysqli->query("INSERT INTO Bulk (created_date, ip, user_id, filename, sources, `recursive`, status, total_rows) VALUES (CURRENT_DATE,'".$_SERVER['REMOTE_ADDR']."',$userid,'".$fileName."','".\implode(',', $sources)."',0,2,'".\count($rows)."')");
$id = $mysqli->insert_id;
$uploaddir = '/opt/bulk/';
$dir = $uploaddir.$id;
if (!\is_dir($dir)) {
    \mkdir($dir, 0777);
}

// copy($workDir.'request.'.$ext, $dir.'/request.'.$ext);
// echo 'zip -j '.$dir.'/request.zip', $workDir.'/request.'.$ext.'<br />';
\shell_exec('zip -j '.$dir.'/request.zip '.$workDir.'/request.'.$ext);
\copy($workDir.'pre.csv', $dir.'/pre.csv');
\file_put_contents($dir.'/fields.txt', \json_encode($fields));
\file_put_contents($dir.'/status.txt', '0');

$select = "SELECT b.id, b.sources, b.recursive, b.status, b.total_rows, s.Login, s.Password, s.Email, s.SiteId FROM  Bulk as b, SystemUsers as s WHERE b.id='".$id."' AND s.Id=b.user_id  ORDER BY b.id DESC LIMIT 1";
$sqlRes = $mysqli->query($select);
$result = $sqlRes->fetch_assoc();

\file_put_contents($dir.'/the.conf', "<?php\n\n\$id = '".$result['Login']."';\n\$passwd = '".$result['Password']."';\n\$serviceurl = '".($result['SiteId'] > 1 ? 'https://my.infohub24.ru/' : 'https://i-sphere.ru/2.00/')."';\n\$sources='".$result['sources']."';\n?>");

$ffix = $result['SiteId'] > 1 ? '' : '/2.00';

foreach ($sources as $source) {
    \mkdir($dir.'/'.$source, 0777);
}

echo 'Реестр '.$_REQUEST['filename'].' отправлен на обработку<br/><br/>';
echo '<a href=$ffix."/bulkPage.php?bulkId='.$id.'">Перейти к результатам</a><br/>';

$msg = 'Загружен реестр '.$id.' в файле '.$_REQUEST['filename'].' от пользователя '.$result['Login'].' по источникам '.\implode(',', $sources);
$mysqli->close();

telegramMsg($msg);

\header('Location: '.$ffix.'/bulkPage.php?bulkId='.$id);
