<?php

include 'config.php';

$mysqli = \mysqli_connect($database['server'], $database['login'], $database['password'], $database['name']) || exit(\mysqli_errno($db).': '.\mysqli_error($db));
if ($mysqli) {
    \mysqli_query($mysqli, 'Set character set utf8');
    \mysqli_query($mysqli, "Set names 'utf8'");
}

// Источники без активных сессий
$sql = <<<SQL
SELECT
code
FROM source
WHERE status>=0 AND id NOT IN (SELECT sourceid FROM session WHERE endtime IS NULL AND sessionstatusid=2)
ORDER BY 1
SQL;

$sqlRes = $mysqli->query($sql);
if (!$sqlRes) {
    \header('HTTP/1.1 500 Internal Server Error');
    return;
}
while ($result = $sqlRes->fetch_assoc()) {
}
$sqlRes->close();

$mysqli->close();

echo 'OK';
