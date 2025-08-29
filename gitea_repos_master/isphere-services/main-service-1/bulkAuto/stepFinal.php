<?php

include('config.php');
include('../config.php');
include('../auth.php');
require('functions.php');

$user_access = get_user_access($mysqli);

if (!$user_access['bulk']) {
    echo json_encode(array('error'=>'У вас нет доступа к загрузке файлов!'));
    exit;
}

$userid = get_user_id($mysqli);
$user_sources = get_user_sources($mysqli);

//print_r($_REQUEST);
//echo "<br>";
//exit;

$login = $_SERVER['PHP_AUTH_USER'];

$fileName = $_REQUEST['filename'];
$workDir = '/opt/upload/'.$login.'_'.$_REQUEST['workFile'].'/';
$ext = pathinfo($fileName, PATHINFO_EXTENSION);

//echo '<br>'.$workDir.$fileName.'<br>';
if(!file_exists($workDir.'request.'.$ext)){
    echo "Реестр отсутствует!";
    exit;
}

$fields = array();
foreach($_REQUEST as $key => $val){
    if(preg_match("/^\d+$/", $key)){
        $fields[$val.($val=="other"?$key:"")] = $key;
    }
}

$checktypes = array();
if($result = mysqli_query($mysqli, 'SELECT * FROM CheckType')){
    while($row = $result->fetch_assoc()){
        $checktypes[$row['code']] = $row;
    }
    mysqli_free_result($result);
}

$sources = array();
$limit = 25;
foreach($_REQUEST['sources'] as $code => $val) if ($checktype=isset($checktypes[$code])?$checktypes[$code]:false){
    if( checkSource($code, $fields) && ((isset($user_sources[$code]) && $user_sources[$code]) || (isset($user_sources[$checktype['source_code']]) && $user_sources[$checktype['source_code']]))){
         $sources[] = $code;
         if (isset($sourcethreads[$code])) {
             if ($limit > $sourcethreads[$code]) $limit = $sourcethreads[$code];
         } else {
             $limit = 1;
         }
    }
}
//print_r($sources);

if(!count($sources)){
    echo "Не выбраны источники для обработки!";
    exit;
}

$rows = explode("\n", file_get_contents($workDir.'pre.csv'));

$mysqli->query("INSERT INTO Bulk (created_date, ip, user_id, filename, sources, `recursive`, status, total_rows) VALUES (CURRENT_DATE,'".$_SERVER['REMOTE_ADDR']."',$userid,'".$fileName."','".implode(',',$sources)."',0,2,".(count($rows)-1).")");
$id = $mysqli->insert_id;
if (!$id) {
    echo "Ошибка при добавлении реестра!";
    exit;
}
$uploaddir = '/opt/bulk/';
$dir = $uploaddir.$id;
if (!is_dir($dir)) mkdir($dir);

//copy($workDir.'request.'.$ext, $dir.'/request.'.$ext);
//echo 'zip -j '.$dir.'/request.zip', $workDir.'/request.'.$ext.'<br />';
shell_exec('zip -j '.$dir.'/request.zip '.$workDir.'/request.'.$ext);
copy($workDir.'pre.csv',$dir.'/pre.csv');
file_put_contents($dir.'/fields.txt', json_encode($fields));

$select = "SELECT b.id, b.sources, b.recursive, b.status, b.total_rows, s.Login, s.Password, s.Email, s.SiteId FROM Bulk as b, SystemUsers as s WHERE b.id='".$id."' AND s.Id=b.user_id  ORDER BY b.id DESC LIMIT 1";
$sqlRes = $mysqli->query($select);
$result = $sqlRes->fetch_assoc();
file_put_contents($dir.'/the.conf', "<?php\n\n\$id = '{$result['Login']}';\n\$passwd = '{$result['Password']}';\n\$serviceurl = '".( $result['SiteId'] > 1 ? 'https://my.infohub24.ru/' : 'https://i-sphere.ru/2.00/' )."';\n\$sources = '{$result['sources']}';\n\$limit = $limit\n?>");
$sqlRes->close();
$mysqli->close();

if (file_exists($dir.'/request.zip') && file_exists($dir.'/pre.csv') && file_exists($dir.'/fields.txt') && file_exists($dir.'/the.conf')) {
    unlink($workDir.'/request.'.$ext);
    unlink($workDir.'/pre.csv');
//    unlink($workDir.'/fields.txt');
//    if (file_exists($workDir.'/fields_auto.txt')) unlink($workDir.'/fields_auto.txt');
//    if (file_exists($workDir.'/fields_auto_sorted.txt')) unlink($workDir.'/fields_auto_sorted.txt');
//    if (file_exists($workDir.'/fields_detected.txt')) unlink($workDir.'/fields_detected.txt');
    array_map('unlink', glob($workDir.'/*.txt'));
    rmdir($workDir);
} else {
    echo "Ошибка при передаче реестра в обработку!";
    exit;
}
// очередь всегда соответствует пользователю
file_put_contents($dir.'/queue.txt', $userid);
if ($limit>=25) {
//// очередь автоматической обработки для 25 потоков соответствует сайту (1-инфосфера 2-инфохаб)
//    file_put_contents($dir.'/queue.txt', $result['SiteId'] > 1 ? 2 : 1);
    file_put_contents($dir.'/status.txt', '0');
    $mode='автоматическую';
} elseif ($limit>=5) {
//// очередь автоматической обработки для 5 потоков всегда 0
//    file_put_contents($dir.'/queue.txt', $userid /*0*/);
    file_put_contents($dir.'/status.txt', '0');
    $mode='автоматическую';
} else {
    $mode='ручную';
}

header("Location: ../bulkinfo.php?id=$id");

//$ffix = $result['SiteId'] > 1 ? '' : '/2.00';
echo "Реестр {$_REQUEST['filename']} отправлен на $mode обработку<br/><br/>";
echo "<a href=\"../bulkinfo.php?id=$id\">Перейти к результатам</a><br/>";

$msg = "Загружен реестр $id на $mode обработку в файле {$_REQUEST['filename']} от пользователя {$result['Login']} по источникам ".implode(',',$sources);
telegramMsg($msg);

include('../footer.php');
