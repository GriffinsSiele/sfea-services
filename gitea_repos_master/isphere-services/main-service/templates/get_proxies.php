<?php

include 'config.php';
include 'auth.php';

$user_access = get_user_access($mysqli);
if (!$user_access['stats']) {
    echo 'У вас нет доступа к этой странице';
    return;
}

$list = [];
$select = 'SELECT DISTINCT id,server,port,login,password,country,proxygroup,status,starttime,lasttime,successtime,endtime';
// $select .= ",(select successrate from proxystats where proxyid=proxy.id) successrate";
$select .= ' FROM proxy WHERE enabled=1';
if (isset($_REQUEST['id']) && \preg_match("/[\d,]+/", $_REQUEST['id'])) {
    $select .= " AND id IN({$_REQUEST['id']})";
    if (isset($_REQUEST['report'])) {
        $mysqli->query('UPDATE proxy SET status='.($_REQUEST['report'] ? '1,successtime=now()' : '0').",lasttime=now() WHERE id IN({$_REQUEST['id']})");
    }
}
if (isset($_REQUEST['country']) && \preg_match('/[a-z]{2}/', $_REQUEST['country'])) {
    $select .= " AND country='{$_REQUEST['country']}'";
}
if (isset($_REQUEST['proxygroup']) && \preg_match('/[0-9]{1}/', $_REQUEST['proxygroup'])) {
    $select .= " AND proxygroup={$_REQUEST['proxygroup']}";
}
if (isset($_REQUEST['status']) && \preg_match('/[0-9]{1}/', $_REQUEST['status'])) {
    $select .= " AND status={$_REQUEST['status']}";
}
if (isset($_REQUEST['order']) && ('starttime' == $_REQUEST['order'] || 'lasttime' == $_REQUEST['order'] || 'successtime' == $_REQUEST['order'] || 'endtime' == $_REQUEST['order'] || 'status' == $_REQUEST['order'] || 'proxygroup' == $_REQUEST['order'] || 'country' == $_REQUEST['order'] || 'server' == $_REQUEST['order'])) {
} else {
    $_REQUEST['order'] = 'id';
}
$select .= " ORDER BY {$_REQUEST['order']}";
if (isset($_REQUEST['limit']) && \preg_match('/[0-9]+/', $_REQUEST['limit'])) {
    $select .= " LIMIT {$_REQUEST['limit']}";
}
$sqlRes = $mysqli->query($select);
while ($result = $sqlRes->fetch_assoc()) {
    $list[] = $result;
}
$sqlRes->close();

if (1 == \count($list)) {
    $mysqli->query('UPDATE proxy SET lasttime=now() WHERE id='.$list[0]['id']);
}

\header('Content-Type: application/json; charset=utf-8');
echo \json_encode($list);
