<?php

function db_connect($database) {
	$db = mysqli_connect($database['server'],$database['login'],$database['password'],$database['name']);
	return $db;
}

function db_close($db) {
	return $db->close();
}

function db_execute($db,$sql) {
	$res = $db->query($sql);
	return $res;
}

function db_select($db,$sql) {
	$res = false;
	$result = $db->query($sql);
	if ($result) {
		if ($row = $result->fetch_row())
			$res = $row[0];
		$result->close();
	}
	return $res;
}

function db_load_xml($db,$queryid,$typeid) {
// Читаем XML
	$sql = "SELECT xml FROM data WHERE queryid=$queryid AND typeid=$typeid";
	if ($xml = db_select($db,$sql)) {
		$encoding = str_between($xml,'encoding="','"');
// Если кодировка не указана явно, перекодируем
//		if (strpos($encoding,'1251')==false)
//			$xml = iconv('CP1251', 'UTF-8', $xml);
	}
	return $xml;
}

function db_save_xml($db,$queryid,$typeid,$xml) {
// Сохраняем XML
	$sql = "DELETE FROM data WHERE queryid=$queryid AND typeid=$typeid";
	db_execute($db,$sql);
	$sqlxml = addslashes($xml);
	$sql = "INSERT INTO data (queryid,typeid,data) VALUES ($queryid,$typeid,'$sqlxml')";
	return db_execute($db,$sql);
}

?>