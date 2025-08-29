<?php

include('config.php');

$db = mysqli_connect ($database['server'],$database['login'],$database['password'], $database['name']) or die(mysqli_errno($db) . ": " . mysqli_error($db));
if ($db) {
    mysqli_query($db, "Set character set utf8");
    mysqli_query($db, "Set names 'utf8'");
}

    $interval = 10;
    $rare_interval = 60;

    $rare = array();
    $response = mysqli_query($db, "SELECT checktype, ROUND(SUM(CASE WHEN res_code < 500 THEN 1 ELSE 0 END)/count(*)*100) AS successrate, ROUND(AVG(process_time),1) as avgtime, count(*) FROM ResponseNew WHERE created_at >= (NOW() - INTERVAL $interval MINUTE) AND res_code > 0 GROUP BY 1 ORDER BY 1;");
    while($row = $response->fetch_row()){
        if ($row[3] >= 20) {
            $stats[$row[0]] = array('successrate'=>floatval($row[1]),'avgtime'=>floatval($row[2]),'interval'=>$interval*60);
        } else {
            $rare[] = "'".$row[0]."'";
        }
        $count[$row[0]] = $row[2];
    }

    $response = mysqli_query($db, "SELECT checktype, ROUND(SUM(CASE WHEN res_code < 500 THEN 1 ELSE 0 END)/count(*)*100) AS successrate, ROUND(AVG(process_time),1) as avgtime, count(*) FROM ResponseNew WHERE created_at >= (NOW() - INTERVAL $rare_interval MINUTE) AND res_code > 0 AND checktype IN (".implode(',',$rare).") GROUP BY 1 ORDER BY 1;");
    while($row = $response->fetch_row()){
        if ($row[3] >= 20) {
            $stats[$row[0]] = array('successrate'=>floatval($row[1]),'avgtime'=>floatval($row[2]),'interval'=>$rare_interval*60);
        }
    }

$db->close();

header("Content-Type: application/json; charset=utf-8");
echo json_encode($stats);
