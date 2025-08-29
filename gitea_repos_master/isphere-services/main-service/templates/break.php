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

$select = "SELECT * FROM isphere.Bulk WHERE id=$id AND status=0 $condition LIMIT 1";

$sqlRes = $mysqli->query($select);
if ($sqlRes->num_rows > 0) {
    $result = $sqlRes->fetch_assoc();
} else {
    echo "Данные реестра $id недоступны";
    return;
}
$sqlRes->close();

//      unlink('/opt/bulk/'.$bulkId.'/status.txt');
\file_put_contents('/opt/bulk/'.$id.'/status.txt', '30');
$files = \glob('/opt/forReq/1/'.$id.'__*');
foreach ($files as $file) {
    \unlink($file);
}

$files = \glob('/opt/forReq/2/'.$id.'__*');
foreach ($files as $file) {
    \unlink($file);
}

$mysqli->query("UPDATE isphere.Bulk SET status=3,results_note='Обработка прервана пользователем' WHERE id=".$id);

echo '<html><head>';
echo '<link rel="stylesheet" type="text/css" href="../public/main.css"/>';
echo '<meta http-equiv="Refresh" content="30"/></head>';
echo '<h1>Реестр номер '.$id.'</h1><hr/><a href="admin.php">К списку реестров</a><br/><br/>';

echo 'Обработка реестра прервана. Спасибо, что Вы с нами.';

include 'footer.php';
