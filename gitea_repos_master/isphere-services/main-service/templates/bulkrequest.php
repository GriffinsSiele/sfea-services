<?php

function mysqli_result($res, $row = 0, $col = 0)
{
    $numrows = \mysqli_num_rows($res);
    if ($numrows && $row <= ($numrows - 1) && $row >= 0) {
        \mysqli_data_seek($res, $row);
        $resrow = (\is_numeric($col)) ? \mysqli_fetch_row($res) : \mysqli_fetch_assoc($res);
        if (isset($resrow[$col])) {
            return $resrow[$col];
        }
    }

    return false;
}

include 'config.php';
include 'auth.php';
include 'xml.php';

$user_access = get_user_access($mysqli);
if (!$user_access['bulk']) {
    echo 'У вас нет доступа к этой странице';
    return;
}

//      $mysqli = mysqli_connect ($database['server'],$database['login'],$database['password'],$database['name']);

$condition = '';
$userid = get_user_id($mysqli);
$user_level = get_user_level($mysqli);
$user_area = get_user_area($mysqli);

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

$id = (isset($_REQUEST['id']) && \preg_match("/^\d+$/", $_REQUEST['id'])) ? $_REQUEST['id'] : '';
if (!$id) {
    echo 'Требуется корректный ID реестра';
    return;
}

$sql = "SELECT * FROM Bulk WHERE id='".$id."'".$condition.' LIMIT 1';
$result = $mysqli->query($sql);

if (!$result || !$result->num_rows) {
    echo "Реестр $id не найден";
    return;
}

$bulk = $result->fetch_assoc();
$result->close();
$bulk['filename'] = \strtr($bulk['filename'], [',' => '.']);
$files = \glob('/opt/bulk/'.$id.'/request.*');
if (\count($files) > 0) {
    $file = $files[0];
    //                header ("Content-Disposition: inline;  filename*0*=result; filename*1*=".$name);
    //                header ("Content-Type:application/zip; name*0*=result; name*1*=".$name);
    //                header('Content-Description: File Transfer');
    \header('Content-Type: application/octet-stream');
    \header('Content-Disposition: attachment; filename='.\pathinfo($bulk['filename'], \PATHINFO_FILENAME).'.'.\pathinfo($file, \PATHINFO_EXTENSION));
    //                header('Content-Transfer-Encoding: binary');
    //                header('Expires: 0');
    //                header('Cache-Control: must-revalidate');
    //                header('Pragma: public');
    echo \file_get_contents($file);
} else {
    //                file_put_contents('BULK_ERRORS.txt', $id."\n", FILE_APPEND);
    echo "Реестр $id отсутствует";
    return;
}
