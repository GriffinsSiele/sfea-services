<?php

include('config.php');
include('auth.php');

$user_access = get_user_access($mysqli);
if (!$user_access['stats']) {
    echo 'У вас нет доступа к этой странице';
    exit;
}

$list = array();
$select = "SELECT DISTINCT id,ifnull(comment,server) name,server,port,login,password,country,proxygroup,status,starttime,lasttime,successtime,endtime";
//$select .= ",(select successrate from proxystats where proxyid=proxy.id) successrate";
$select .= " FROM proxy WHERE id>0";
if (isset($_REQUEST['id'])) {
    if (!preg_match("/^[-\d,]+$/",$_REQUEST['id'])) $_REQUEST['id']="0";
    $select .= " AND id IN({$_REQUEST['id']})";
    if (isset($_REQUEST['report']))
        $mysqli->query("UPDATE proxy SET status=".($_REQUEST['report']?"1,successtime=now()":"0,lasttime=now()")." WHERE id IN({$_REQUEST['id']})");
}
if (isset($_REQUEST['country']) && preg_match("/^[a-z]+$/",$_REQUEST['country'])) {
    $select .= " AND country='{$_REQUEST['country']}'";
}
if (isset($_REQUEST['proxygroup']) && preg_match("/^[\d,]+$/",$_REQUEST['proxygroup'])) {
    $select .= " AND proxygroup IN ({$_REQUEST['proxygroup']})";
}
if (isset($_REQUEST['enabled']) && preg_match("/^[0-9]{1}$/",$_REQUEST['enabled'])) {
    $select .= " AND enabled={$_REQUEST['enabled']}";
} elseif (!isset($_REQUEST['enabled']) && !isset($_REQUEST['id'])) {
    $select .= " AND enabled=1";
}
if (isset($_REQUEST['status']) && preg_match("/^[0-9]{1}$/",$_REQUEST['status'])) {
    $select .= " AND status={$_REQUEST['status']}";
}
if (isset($_REQUEST['order']) && ($_REQUEST['order']=='starttime' || $_REQUEST['order']=='lasttime' || $_REQUEST['order']=='successtime' || $_REQUEST['order']=='endtime' || $_REQUEST['order']=='status' || $_REQUEST['order']=='status desc' || $_REQUEST['order']=='proxygroup' || $_REQUEST['order']=='proxygroup desc' || $_REQUEST['order']=='country' || $_REQUEST['order']=='server')) {
} else {
    $_REQUEST['order'] = "lasttime";
}
if ($_REQUEST['order']!="lasttime")
    $_REQUEST['order'].=",lasttime";
$select .= " ORDER BY {$_REQUEST['order']}";
if (isset($_REQUEST['limit']) && preg_match("/^[0-9]+$/",$_REQUEST['limit'])) {
    $select .= " LIMIT {$_REQUEST['limit']}";
}
$sqlRes = $mysqli->query($select);
while($result = $sqlRes->fetch_assoc()){
    $list[] = $result;
}
$sqlRes->close();

if (sizeof($list)==1) {
    $mysqli->query("UPDATE proxy SET lasttime=now() WHERE id=".$list[0]['id']);
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode($list);
