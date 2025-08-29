<?php
      include ('config.php');
      include ('auth.php');

      $user_access = get_user_access($mysqli);
      $clientid = get_client_id($mysqli);
      if ($clientid!=265/* && !$user_access['stats']*/) {
          echo 'У вас нет доступа к этой странице';
          exit;
      }

//      $mysqli = mysqli_connect ($database['server'],$database['login'],$database['password'], $database['name']);

      echo '<link rel="stylesheet" type="text/css" href="main.css"/>';
      echo '<h1>Состояние источников</h1><hr/><a href="admin.php">Назад</a><br/><br/>';

      $userid = get_user_id($mysqli);
      $user_level = get_user_level($mysqli);
      $user_area = get_user_area($mysqli);
      $conditions = '';
      $users = '';
      $users_list = '';

      $select = "SELECT Id, Login, Locked FROM isphere.SystemUsers";
      if ($user_area<=2) {
          $select .= " WHERE Id=$userid";
          if ($user_area>=1) {
              $select .= "  OR MasterUserId=$userid";
              if ($user_area>1) {
                  $select .= " OR MasterUserId IN (SELECT id FROM SystemUsers WHERE MasterUserId=$userid)";
              }
          } else
              $_REQUEST['user_id'] = $userid;
      }
      $select .= " ORDER BY Login";
      $sqlRes = $mysqli->query($select);
      if ($sqlRes->num_rows>1) {
          while($result = $sqlRes->fetch_assoc()){
              $users_list .= ($users_list?',':'').$result['Id'];
          }
          if ($user_area<=2) {
              $conditions .= ' AND r.user_id IN ('.$users_list.')';
          }
      }
      $sqlRes->close();

      $hours = isset($_REQUEST['hours']) && (preg_match("/^[0-9]{1,2}$/",$_REQUEST['hours']) || preg_match("/^0\:[0-5][0-9]$/",$_REQUEST['hours'])) ? $_REQUEST['hours'] : '1';
      $fromtime = 'date_sub(now(),interval '.(strpos($hours,':')?substr($hours,2).' minute':$hours.' hour').')';

      echo '<form action="">';
      echo '<select name="hours">';
      echo '<option value="0:01"'.($hours=='0:01'?' selected':'').'>За последнюю минуту</option>';
      echo '<option value="0:02"'.($hours=='0:02'?' selected':'').'>За последние 2 минуты</option>';
      echo '<option value="0:05"'.($hours=='0:05'?' selected':'').'>За последние 5 минут</option>';
      echo '<option value="0:15"'.($hours=='0:15'?' selected':'').'>За последние 15 минут</option>';
      echo '<option value="0:30"'.($hours=='0:30'?' selected':'').'>За последние 30 минут</option>';
      echo '<option value="1"'.($hours=='1'?' selected':'').'>За последний час</option>';
      echo '<option value="3"'.($hours=='3'?' selected':'').'>За 3 часа</option>';
      echo '<option value="6"'.($hours=='6'?' selected':'').'>За 6 часов</option>';
      echo '<option value="12"'.($hours=='12'?' selected':'').'>За 12 часов</option>';
      echo '<option value="24"'.($hours=='24'?' selected':'').'>За 24 часа</option>';
      echo '<option value="72"'.($hours=='72'?' selected':'').'>За 3 суток</option>';
      echo '</select>';
      echo ' <input type="submit" value="Обновить"></form>';

      $conditions .= ' AND r.created_at >= '.$fromtime;

      $tmpview = 'STAT_'.$userid.'_'.time();

      $field = 'checktype'; //'source_name';

      $sql = <<<SQL
CREATE VIEW $tmpview AS
SELECT
request_id,
$field,
user_id,
MAX(process_time) process_time,
SUM(1) total,
SUM(res_code<>500) success,
SUM(res_code=500) error,
SUM(res_code=200) found
FROM ResponseNew r
WHERE res_code<>0
$conditions
GROUP BY 1,2,3
SQL;

// echo strtr($sql,array("\n"=>"<br>"))."\n";

      $mysqli->query($sql);

      $sql = <<<SQL
SELECT
$field,
COUNT(*) rescount,
SUM(r.success>0) success
, SUM(r.error>0) error
, SUM(r.found>0) hit
, AVG(r.process_time) process
FROM $tmpview r, SystemUsers u, SystemUsers m
WHERE r.user_id=u.id AND m.id=CASE WHEN u.id=$userid OR u.MasterUserID IS NULL OR u.MasterUserID=0 THEN u.id ELSE u.MasterUserId END
GROUP BY 1
ORDER BY 1
SQL;

// echo strtr($sql,array("\n"=>"<br>"))."\n";

      $title = array(
          "source_name" => "Источник",
          "checktype" => "Проверка",
          "text" => "Ошибка",
          "rescount" => "Запросов",
          "success" => "Успешно",
          "hit" => "Найдено",
          "error" => "Ошибок",
          "process" => "Время, c",
          "successrate" => "Доля успешных, %",
          "hitrate" => "Доля найденных, %",
          "type" => "Тип",
          "processing" => "Выполняется",
          "completed" => "Выполнено",
          "timeout" => "Просрочено",
      );
      $total = array(
          "rescount" => 0,
          "success" => 0,
          "hit" => 0,
          "error" => 0,
          "processing" => 0,
          "completed" => 0,
          "timeout" => 0,
      );

      $i = 0;
      $sqlRes = $mysqli->query($sql);
      echo '<h2>Статистика по проверкам</h2>';
      echo "<table border=1>\n";
      while($result = $sqlRes->fetch_assoc()){
//              print_r($result);
                if ($i==0) {
                    $first = $result;
                    echo "<tr>\n";
                    foreach ($result as $key => $val) {
                        echo "<th>".$title[$key]."</th>";
                    }
                    echo "<th>".$title["successrate"]."</th>";
                    echo "<th>".$title["hitrate"]."</th>";
                }
		echo "</tr>\n";
                echo "<tr".(($result["rescount"]&&!$result["success"]) || $result['process']>60 ? " style=\"color:red\"":"").">\n";
                echo "<tr".(($result["rescount"]&&!$result["success"]) || $result['process']>60 ? " style=\"color:red\"":"").">\n";
                foreach ($result as $key => $val) {
                    if ($key=="process") $val = number_format($val,1,',','');
                    echo "<td ".(is_numeric($val)?"class=\"right\"":"").">".$val."</td>";
                    if (isset($total[$key])) $total[$key]+=$val;
                }
                echo "<td class=\"right\">".($result["rescount"]?number_format($result["success"]/$result["rescount"]*100,2,',',''):"")."</td>";
                echo "<td class=\"right\">".($result["success"]?number_format($result["hit"]/$result["success"]*100,2,',',''):"")."</td>";
		echo "</tr>\n";
                $i++;
      }
      if ($i == 0) {
          echo "Нет данных";
      } else {
                foreach ($first as $key => $val) {
                    echo "<td class=\"right\"><b>".(isset($total[$key])?$total[$key]:"")."</b></td>";
                }
//                echo "<td class=\"right\"><b>".($total["rescount"]>10?number_format($total["success"]/$total["rescount"]*100,2,',',''):"")."</b></td>";
//                echo "<td class=\"right\"><b>".($total["success"]>10?number_format($total["hit"]/$total["success"]*100,2,',',''):"")."</b></td>";
                echo "<td class=\"right\"></td>";
                echo "<td class=\"right\"></td>";
      }
      $sqlRes->close();
      $mysqli->query("DROP VIEW $tmpview");
      echo "</table><br />\n";

      $sql = <<<SQL
SELECT type
, SUM(status=0) processing
, SUM(status=1) completed
, SUM(status=-1) timeout
, AVG(TIMESTAMPDIFF(second,created_at,CASE WHEN processed_at IS NULL THEN now() ELSE processed_at END)) process
FROM RequestNew r
WHERE 1 = 1
$conditions
GROUP BY 1
ORDER BY 1;
SQL;

      $i = 0;
      $sqlRes = $mysqli->query($sql);
      echo '<h2>Статистика по входящим запросам</h2>';
      echo "<table border=1>\n";
      while($result = $sqlRes->fetch_assoc()){
                if ($i==0) {
                    $first = $result;
                    echo "<tr>\n";
                    foreach ($result as $key => $val) {
                        echo "<th>".$title[$key]."</th>";
                    }
                }
		echo "</tr>\n";
                foreach ($result as $key => $val) {
                    if ($key=="type" && !$val) $val = "api";
                    if ($key=="process") $val = number_format($val,1,',','');
                    echo "<td ".(is_numeric($val)?"class=\"right\"":"").">".$val."</td>";
                    if (isset($total[$key])) $total[$key]+=$val;
                }
		echo "</tr>\n";
                $i++;
      }
      if ($i == 0) {
          echo "Нет данных";
      } else {
		echo "</tr>\n";
                foreach ($first as $key => $val) {
                    echo "<td class=\"right\"><b>".(isset($total[$key])?$total[$key]:"")."</b></td>";
                }
		echo "</tr>\n";
      }
      $sqlRes->close();
      $mysqli->query("DROP VIEW $tmpview");
      echo "</table><br />\n";

      $sql = <<<SQL
SELECT
$field,
text, count(*) error
FROM ResponseNew r
LEFT JOIN ResponseError e ON e.response_id=r.id
WHERE r.res_code >= 500
$conditions
GROUP BY 1,2
ORDER BY 1,2;
SQL;

      $i = 0;
      $sqlRes = $mysqli->query($sql);
      echo '<h2>Статистика по ошибкам</h2>';
      echo "<table border=1>\n";
      while($result = $sqlRes->fetch_assoc()){
                if ($i==0) {
                    echo "<tr>\n";
                    foreach ($result as $key => $val) {
                        echo "<th>".$title[$key]."</th>";
                    }
                }
		echo "</tr>\n";
                foreach ($result as $key => $val) {
                    echo "<td ".(is_numeric($val)?"class=\"right\"":"").">".$val."</td>";
                }
		echo "</tr>\n";
                $i++;
      }
      if ($i == 0) {
          echo "Нет данных";
      }
      $sqlRes->close();
      $mysqli->query("DROP VIEW $tmpview");
      echo "</table><br />\n";

if ($user_level<0) {
      $sql = <<<SQL
SELECT
code
,(SELECT COUNT(*) FROM session WHERE sourceid=source.id AND endtime IS NULL AND sessionstatusid=2) active
,min_sessions
,(SELECT COUNT(*) FROM session WHERE sourceid=source.id AND endtime IS NULL AND sessionstatusid IN (1,7)) pending
,(SELECT COUNT(*) FROM session WHERE sourceid=source.id AND endtime IS NULL AND sessionstatusid=6) locked
,(SELECT COUNT(*) FROM session WHERE sourceid=source.id AND starttime>$fromtime) started
,(SELECT COUNT(*) FROM session WHERE sourceid=source.id AND captcha<>'' AND starttime>$fromtime) captchas
,(SELECT COUNT(*) FROM session WHERE sourceid=source.id AND captcha<>'' AND starttime>$fromtime AND sessionstatusid=4) invalidcaptchas
,(SELECT COUNT(DISTINCT proxyid) FROM session WHERE sourceid=source.id AND starttime>$fromtime) proxies
,(SELECT COUNT(*) FROM session WHERE sourceid=source.id AND lasttime<>starttime AND lasttime>$fromtime) used
,(SELECT COUNT(*) FROM session WHERE sourceid=source.id AND endtime IS NOT NULL AND endtime>$fromtime) finished
,(SELECT COUNT(*) FROM session WHERE sourceid=source.id AND endtime IS NOT NULL AND sessionstatusid=3 AND endtime>$fromtime) exhausted
,(SELECT COUNT(*) FROM session WHERE sourceid=source.id AND endtime IS NOT NULL AND sessionstatusid=5 AND endtime>$fromtime) expired
,(SELECT MIN(unix_timestamp(endtime)-unix_timestamp(starttime))/60 FROM session WHERE sourceid=source.id AND endtime IS NOT NULL AND sessionstatusid=3 AND endtime>$fromtime) minlifetime
,(SELECT AVG(unix_timestamp(endtime)-unix_timestamp(starttime))/60 FROM session WHERE sourceid=source.id AND endtime IS NOT NULL AND sessionstatusid=3 AND endtime>$fromtime) avglifetime
,(SELECT MAX(unix_timestamp(endtime)-unix_timestamp(starttime))/60 FROM session WHERE sourceid=source.id AND endtime IS NOT NULL AND sessionstatusid=3 AND endtime>$fromtime) maxlifetime
,(SELECT SUM(success) FROM session WHERE sourceid=source.id AND endtime IS NOT NULL AND success IS NOT NULL AND endtime>$fromtime) success
,(SELECT AVG(success) FROM session WHERE sourceid=source.id AND endtime IS NOT NULL AND success IS NOT NULL AND endtime>$fromtime) successrate
FROM source
WHERE status>=0 and code NOT IN ('getcontact_app','gosuslugi')
UNION
SELECT
code
,(SELECT COUNT(*) FROM session_getcontact WHERE sourceid=source.id AND endtime IS NULL AND sessionstatusid=2) active
,min_sessions
,(SELECT COUNT(*) FROM session_getcontact WHERE sourceid=source.id AND endtime IS NULL AND sessionstatusid IN (1,7)) pending
,(SELECT COUNT(*) FROM session_getcontact WHERE sourceid=source.id AND endtime IS NULL AND sessionstatusid=6) locked
,(SELECT COUNT(*) FROM session_getcontact WHERE sourceid=source.id AND starttime>$fromtime) started
,(SELECT COUNT(*) FROM session_getcontact WHERE sourceid=source.id AND captcha<>'' AND starttime>$fromtime) captchas
,(SELECT COUNT(*) FROM session_getcontact WHERE sourceid=source.id AND captcha<>'' AND starttime>$fromtime AND sessionstatusid=4) invalidcaptchas
,(SELECT COUNT(DISTINCT proxyid) FROM session_getcontact WHERE sourceid=source.id AND starttime>$fromtime) proxies
,(SELECT COUNT(*) FROM session_getcontact WHERE sourceid=source.id AND lasttime<>starttime AND lasttime>$fromtime) used
,(SELECT COUNT(*) FROM session_getcontact WHERE sourceid=source.id AND endtime IS NOT NULL AND endtime>$fromtime) finished
,(SELECT COUNT(*) FROM session_getcontact WHERE sourceid=source.id AND endtime IS NOT NULL AND sessionstatusid=3 AND endtime>$fromtime) exhausted
,(SELECT COUNT(*) FROM session_getcontact WHERE sourceid=source.id AND endtime IS NOT NULL AND sessionstatusid=5 AND endtime>$fromtime) expired
,(SELECT MIN(unix_timestamp(endtime)-unix_timestamp(starttime))/60 FROM session_getcontact WHERE sourceid=source.id AND endtime IS NOT NULL AND sessionstatusid=3 AND endtime>$fromtime) minlifetime
,(SELECT AVG(unix_timestamp(endtime)-unix_timestamp(starttime))/60 FROM session_getcontact WHERE sourceid=source.id AND endtime IS NOT NULL AND sessionstatusid=3 AND endtime>$fromtime) avglifetime
,(SELECT MAX(unix_timestamp(endtime)-unix_timestamp(starttime))/60 FROM session_getcontact WHERE sourceid=source.id AND endtime IS NOT NULL AND sessionstatusid=3 AND endtime>$fromtime) maxlifetime
,(SELECT SUM(success) FROM session_getcontact WHERE sourceid=source.id AND endtime IS NOT NULL AND success IS NOT NULL AND endtime>$fromtime) success
,(SELECT AVG(success) FROM session_getcontact WHERE sourceid=source.id AND endtime IS NOT NULL AND success IS NOT NULL AND endtime>$fromtime) successrate
FROM source
WHERE status>=0 and code='getcontact_app'
UNION
SELECT
code
,(SELECT COUNT(*) FROM session_gosuslugi WHERE sourceid=source.id AND endtime IS NULL AND sessionstatusid=2) active
,min_sessions
,(SELECT COUNT(*) FROM session_gosuslugi WHERE sourceid=source.id AND endtime IS NULL AND sessionstatusid IN (1,7)) pending
,(SELECT COUNT(*) FROM session_gosuslugi WHERE sourceid=source.id AND endtime IS NULL AND sessionstatusid=6) locked
,(SELECT COUNT(*) FROM session_gosuslugi WHERE sourceid=source.id AND starttime>$fromtime) started
,(SELECT COUNT(*) FROM session_gosuslugi WHERE sourceid=source.id AND captcha<>'' AND starttime>$fromtime) captchas
,(SELECT COUNT(*) FROM session_gosuslugi WHERE sourceid=source.id AND captcha<>'' AND starttime>$fromtime AND sessionstatusid=4) invalidcaptchas
,(SELECT COUNT(DISTINCT proxyid) FROM session_gosuslugi WHERE sourceid=source.id AND starttime>$fromtime) proxies
,(SELECT COUNT(*) FROM session_gosuslugi WHERE sourceid=source.id AND lasttime<>starttime AND lasttime>$fromtime) used
,(SELECT COUNT(*) FROM session_gosuslugi WHERE sourceid=source.id AND endtime IS NOT NULL AND endtime>$fromtime) finished
,(SELECT COUNT(*) FROM session_gosuslugi WHERE sourceid=source.id AND endtime IS NOT NULL AND sessionstatusid=3 AND endtime>$fromtime) exhausted
,(SELECT COUNT(*) FROM session_gosuslugi WHERE sourceid=source.id AND endtime IS NOT NULL AND sessionstatusid=5 AND endtime>$fromtime) expired
,(SELECT MIN(unix_timestamp(endtime)-unix_timestamp(starttime))/60 FROM session_gosuslugi WHERE sourceid=source.id AND endtime IS NOT NULL AND sessionstatusid=3 AND endtime>$fromtime) minlifetime
,(SELECT AVG(unix_timestamp(endtime)-unix_timestamp(starttime))/60 FROM session_gosuslugi WHERE sourceid=source.id AND endtime IS NOT NULL AND sessionstatusid=3 AND endtime>$fromtime) avglifetime
,(SELECT MAX(unix_timestamp(endtime)-unix_timestamp(starttime))/60 FROM session_gosuslugi WHERE sourceid=source.id AND endtime IS NOT NULL AND sessionstatusid=3 AND endtime>$fromtime) maxlifetime
,(SELECT SUM(success) FROM session_gosuslugi WHERE sourceid=source.id AND endtime IS NOT NULL AND success IS NOT NULL AND endtime>$fromtime) success
,(SELECT AVG(success) FROM session_gosuslugi WHERE sourceid=source.id AND endtime IS NOT NULL AND success IS NOT NULL AND endtime>$fromtime) successrate
FROM source
WHERE status>=0 and code='gosuslugi'
ORDER BY 1
SQL;

// echo strtr($sql,array("\n"=>"<br>"))."\n";

      $title = array(
          "code" => "Источник",
          "active" => "Активно",
          "min_sessions" => "Минимум",
          "pending" => "Ожидается",
          "locked" => "Приостановлено",
          "started" => "Создано",
          "captchas" => "Распознано капч",
          "invalidcaptchas" => "Неверных капч",
          "proxies" => "Прокси",
          "used" => "Задействовано",
          "finished" => "Завершено",
          "exhausted" => "Использовано",
          "expired" => "Отменено",
          "minlifetime" => "Мин.длит., мин",
          "avglifetime" => "Ср.длит., мин",
          "maxlifetime" => "Макс.длит., мин",
          "success" => "Запросов",
          "successrate" => "Запросов на сессию",
      );
      $total = array(
          "captchas" => 0,
          "invalidcaptchas" => 0,
      );

      $i = 0;
      $sqlRes = $mysqli->query($sql);
      echo '<h2>Статистика по сессиям</h2>';
      echo "<table border=1>\n";
      while($result = $sqlRes->fetch_assoc()){
//              print_r($result);
                if ($i==0) {
                    $first = $result;
                    echo "<tr>\n";
                    foreach ($result as $key => $val) {
                        if (isset($title[$key])) echo "<th>".$title[$key]."</th>";
                    }
//                    echo "<th>".$title["successrate"]."</th>";
//                    echo "<th>".$title["hitrate"]."</th>";
                }
		echo "</tr>\n";
                echo "<tr".($result['active']<$result['min_sessions']?" style=\"color:red\"":"").">\n";
                foreach ($result as $key => $val) {
                    if (strpos($key,"time")!==false) $val = $val ? number_format($val,1,',','') : '';
                    if (isset($title[$key])) echo "<td ".(is_numeric($val)?"class=\"right\"":"").">".$val."</td>";
                    if (isset($total[$key])) $total[$key]+=$val;
                }
//                echo "<td class=\"right\">".($result["rescount"]>10?number_format($result["success"]/$result["rescount"]*100,2,',',''):"")."</td>";
//                echo "<td class=\"right\">".($result["success"]>10?number_format($result["hit"]/$result["success"]*100,2,',',''):"")."</td>";
		echo "</tr>\n";
                $i++;
      }
      if ($i == 0) {
          echo "Нет данных";
      } else {
                foreach ($first as $key => $val) {
                    echo "<td class=\"right\"><b>".(isset($total[$key])?$total[$key]:"")."</b></td>";
                }
//                echo "<td class=\"right\"><b>".($total["rescount"]>10?number_format($total["success"]/$total["rescount"]*100,2,',',''):"")."</b></td>";
//                echo "<td class=\"right\"><b>".($total["success"]>10?number_format($total["hit"]/$total["success"]*100,2,',',''):"")."</b></td>";
      }
      $sqlRes->close();
      echo "</table><br />\n";
      echo "Активно - кол-во текущих сессий для запросов<br />\n";
      echo "Приостановлено - кол-во текущих сессий с превышенными лимитами запросов<br />\n";
      echo "Создано - кол-во новых сессий за выбранный период<br />\n";
      echo "Распознано капч - кол-во распознанных капч для новых сессий за выбранный период<br />\n";
      echo "Неверных капч - кол-во неверно распознанных капч в сессиях за выбранный период (их может оказаться больше после использования активных сессий)<br />\n";
      echo "Прокси - кол-во прокси, использованных для создания сессий за выбранный период<br />\n";
      echo "Задействовано - кол-во сессий, использованных для запросов за выбранный период<br />\n";
      echo "Завершено - общее кол-во сессий, завершенных за выбранный период<br />\n";
      echo "Использовано - кол-во успешно использованных сессий, завершенных за выбранный период<br />\n";
      echo "Отменено - кол-во отмененных сессий, завершенных за выбранный период (просрочены или прокси недоступен)<br />\n";
      echo "Мин./ср./макс. длительность - время жизни успешно использованных сессий, завершенных за выбранный период<br />\n";
      echo "Запросов - кол-во успешных запросов в сессиях, завершенных за выбранный период<br />\n";
      echo "Запросов на сессию - среднее кол-во успешных запросов в сессиях, завершенных за выбранный период<br />\n";
}

      $mysqli->close();

include('footer.php');
