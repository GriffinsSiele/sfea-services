<?php

include('config.php');
include('auth.php');

$user_access = get_user_access($mysqli);
if (!$user_access['sources']) {
    echo 'У вас нет доступа к этой странице';
    exit;
}
$user_sources = get_user_sources($mysqli);

$db = mysqli_connect ($database['server'],$database['login'],$database['password'], $database['name']) or die(mysqli_errno($db) . ": " . mysqli_error($db));
if ($db) {
    mysqli_query($db, "Set character set utf8");
    mysqli_query($db, "Set names 'utf8'");
}

    $interval = 60;
    $response = mysqli_query($db, "SELECT checktype, COUNT(*) total, SUM(CASE WHEN res_code < 500 THEN 1 ELSE 0 END) success, SUM(CASE WHEN res_code = 200 THEN 1 ELSE 0 END) found, ROUND(AVG(process_time),3) as avgtime FROM ResponseNew WHERE created_at >= (NOW() - INTERVAL $interval MINUTE) AND res_code > 0 GROUP BY 1 ORDER BY 1;");
    while($row = $response->fetch_row()){
        if ($row[1] >= 10) {
            $stats[$row[0]] = array('successrate'=>floatval($row[2]/$row[1]*100),'hitrate'=>floatval($row[2]?$row[3]/$row[2]*100:-1),'avgtime'=>floatval($row[4]));
        }
    }

    echo '<link rel="stylesheet" type="text/css" href="main.css"/>';
    echo "<h1>Актуальный перечень источников от ".date('d.m.Y')."</h1>\n<hr/><a href=\"admin.php\">Назад</a><br/><br/>\n";

    echo "<table border=1><tr><th>Код проверки</th><th>Код источника</th><th>Описание</th><th>Статус</th><th>Ср.время,с</th><th>Успешно,%</th><th>Найдено,%</th></tr>\n";
    $statuses = array(
        -1 => 'Недоступен',
         0 => 'Временно выключен',
         1 => 'Доступен',
    );
    $response = mysqli_query($db, "SELECT * FROM isphere.CheckType WHERE status>=-1 ORDER BY code");
    while($row = $response->fetch_assoc()){
        $code = $row['code'];
        $source_code = $row['source_code'];
        $status = $statuses[intval($row['status'])];
        if ((isset($user_sources[$code]) && $user_sources[$code]) || (isset($user_sources[$source_code]) && $user_sources[$source_code])){
            if (isset($stats[$code])) {
                $row['avgtime'] = number_format($stats[$code]['avgtime'],1);
                $row['successrate'] = number_format($stats[$code]['successrate'],1);
                $row['hitrate'] = $stats[$code]['hitrate']<0?'':number_format($stats[$code]['hitrate'],1);
                if ($row['status']==1 && isset($row['successrate'])) {
                    if ($row['successrate']>90) $status='Работает';
                    elseif ($row['successrate']<10) $status='Не работает';
                    else $status='Работает с ошибками';
                }
            } else {
                $row['avgtime'] = '';
                $row['successrate'] = '';
                $row['hitrate'] = '';
            }
            echo "<tr><td><a href=\"sourceinfo.php?checktype=$code\"><b>$code</b></a></td><td>$source_code</td><td>{$row['title']}</td><td>$status</td><td class=\"right\">{$row['avgtime']}</td><td class=\"right\">{$row['successrate']}</td><td class=\"right\">{$row['hitrate']}</td></tr>\n";
        }
    }
    echo "</table><br/>\n";

$db->close();

include('footer.php');
