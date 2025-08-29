<?php

include 'config.php';

$mysqli = \mysqli_connect($database['server'], $database['login'], $database['password'], $database['name']) || exit(\mysqli_errno($db).': '.\mysqli_error($db));
if ($mysqli) {
    \mysqli_query($mysqli, 'Set character set utf8');
    \mysqli_query($mysqli, "Set names 'utf8'");
}

$user_id = 3178;
$checktypes = ['gosuslugi_phone', 'gosuslugi_passport', 'ok_phone', 'ok_email', 'vk_phone', 'vk_email', 'vk_phonecheck', 'vk_emailcheck', 'vk_url'];
$date = '2022-12-01';
while ($date < '2023-01-01') {
    foreach ($checktypes as $checktype) {
        $mysqli->query('DROP TABLE dup_ext');
        $mysqli->query('DROP TABLE dup_req');
        $mysqli->query('DROP TABLE dup_req_del');

        $sql = <<<SQL
CREATE TABLE dup_ext AS
SELECT external_id FROM `RequestNew` WHERE created_date='$date' AND user_id=$user_id
AND id IN (SELECT request_id FROM ResponseNew WHERE created_date='$date' AND user_id=$user_id AND checktype='$checktype')
group by 1 having count(*)>1
SQL;
        $mysqli->query($sql);

        $sql = <<<SQL
CREATE TABLE dup_req AS
SELECT id,external_id FROM `RequestNew` WHERE created_date='$date' AND user_id=$user_id AND external_id IN (SELECT external_id FROM `dup_ext`)
AND id IN (SELECT request_id FROM ResponseNew WHERE created_date='$date' AND user_id=$user_id AND checktype='$checktype')
SQL;
        $mysqli->query($sql);

        $mysqli->query('CREATE INDEX external_id ON dup_req(external_id)');
        $sql = <<<SQL
CREATE TABLE dup_req_del
SELECT id FROM `dup_req` r WHERE id<>(SELECT max(id) FROM dup_req WHERE external_id=r.external_id)
;
SQL;
        $mysqli->query($sql);

        $sql = <<<SQL
SELECT id FROM ResponseNew
WHERE created_date='$date' AND user_id=$user_id AND checktype='$checktype' AND res_code IN (200,204) AND request_id IN
(SELECT id FROM `dup_req_del`)
;
SQL;
        $result = $mysqli->query($sql);
        if ($result && $result->num_rows) {
            $q = 'UPDATE ResponseNew SET res_code=0 WHERE id IN(';
            while ($row = $result->fetch_row()) {
                $q .= $row[0].',';
            }
            $q .= '0);';
            $mysqli->query($q);
            //        echo "$q\n";
            echo "$date $checktype fixed {$result->num_rows} rows\n";
            $result->close();
            \sleep(5);
        }
    }
    $date = \date('Y-m-d', \strtotime($date.'+ 1 days'));
}

$mysqli->query('DROP TABLE dup_ext');
$mysqli->query('DROP TABLE dup_req');
$mysqli->query('DROP TABLE dup_req_del');
$mysqli->close();
