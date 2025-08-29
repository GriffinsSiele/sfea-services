<?php
      include ('config.php');
      include ('auth.php');

      $user_access = get_user_access($mysqli);
      if (!$user_access['reports']) {
          echo 'У вас нет доступа к этой странице';
          exit;
      }

      set_time_limit(600);

      $mysqls = mysqli_init();
      mysqli_options($mysqls,MYSQLI_OPT_CONNECT_TIMEOUT,$dbstat['connect_timeout']);
      mysqli_options($mysqls,MYSQLI_OPT_READ_TIMEOUT,$dbstat['read_timeout']);
//      $mysqls = mysqli_connect($dbstat['server'],$dbstat['login'],$dbstat['password'], $dbstat['name']);
//      if ($mysqls) {
      if (mysqli_real_connect($mysqls,$dbstat['server'],$dbstat['login'],$dbstat['password'],$dbstat['name'])) {
          $mysqli->close();
          $mysqli = $mysqls;
          mysqli_query($mysqli, "Set character set utf8");
          mysqli_query($mysqli, "Set names 'utf8'");
      } else {
//          header('HTTP/1.1 500 Internal Server Error'); 
//          echo 'Статистика по запросам временно недоступна.';
//          exit();
      }

      echo '<link rel="stylesheet" type="text/css" href="main.css"/>';
      echo '<h1>Статистика запросов</h1><hr/><a href="admin.php">Назад</a><br/><br/>';

      $type = isset($_REQUEST['type'])?$_REQUEST['type']:'';

      $userid = get_user_id($mysqli);
      $user_level = get_user_level($mysqli);
      $user_area = get_user_area($mysqli,"ReportsArea");
      $clientid = get_client_id($mysqli);
      $conditions = '';
      $rep_conditions = '';
      $users = '';
      $users_list = '';

      echo '<form action="">';

      $select = "SELECT Id, Code FROM isphere.Client ORDER BY Code";
      $sqlRes = $mysqli->query($select);
      if ($sqlRes->num_rows>1) {
          $clients = '<select name="client_id"><option value="">Все клиенты</option>';
          $clients .= '<option value="0"'.(isset($_REQUEST['client_id']) && $_REQUEST['client_id']==="0" ? ' selected' : '').'>Без договора</option>';
          while($result = $sqlRes->fetch_assoc()){
              $clients .= '<option value="'.$result['Id'].'"'.(isset($_REQUEST['client_id']) && $result['Id']==$_REQUEST['client_id'] ? ' selected' : '').'>'.$result['Code'].'</option>';
          }
          $clients .= '</select>';
      }
      $sqlRes->close();
      if ($user_area>2) {
          echo $clients;
      } else {
          $_REQUEST['client_id'] = $clientid;
      }

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
          $users = '<select name="user_id"><option value="">Все пользователи</option>';
          while($result = $sqlRes->fetch_assoc()){
              $users .= '<option value="'.$result['Id'].'"'.(isset($_REQUEST['user_id']) && $result['Id']==$_REQUEST['user_id'] ? ' selected' : '').'>'.$result['Login'].($result['Locked']?' (-)':'').'</option>';
              $users_list .= ($users_list?',':'').$result['Id'];
          }
          $users .= '</select>';
          if ($user_area<=2) {
              $conditions .= ' AND user_id IN ('.$users_list.')';
          }
      } else {
          $_REQUEST['user_id'] = $userid;
      }
      $sqlRes->close();
      echo $users;
      if ($user_area>=2) {
          echo ' <input type="checkbox" name="nested"'.(isset($_REQUEST['nested']) && $_REQUEST['nested']?' checked="checked"':'').'>+дочерние';
      }

      echo ' Период с <input type="text" name="from" value="'.(isset($_REQUEST['from'])?$_REQUEST['from']:date('d.m.Y')).'">';
      echo ' по <input type="text" name="to" value="'.(isset($_REQUEST['to'])?$_REQUEST['to']:date('d.m.Y')).'">';
      if ($user_level<0) {
          echo ' IP <input type="text" name="ip" value="'.(isset($_REQUEST['ip'])?$_REQUEST['ip']:'').'">';
      }
      echo '</br>';
//      if ($user_level<0) {
          $select = "SELECT DISTINCT source_name FROM isphere.ResponseNew ORDER BY 1";
          echo ' <select name="source"><option value="">Все источники</option>';
          $sqlRes = $mysqli->query($select);
          while($result = $sqlRes->fetch_assoc()){
              echo '<option value="'.$result['source_name'].'"'.(isset($_REQUEST['source']) && $result['source_name']==$_REQUEST['source'] ? ' selected' : '').'>'.$result['source_name'].'</option>';
          }
          echo '</select>';
          $sqlRes->close();
//      }
//      if ($user_level<0) {
          $select = "SELECT DISTINCT checktype FROM isphere.ResponseNew ORDER BY 1";
          echo ' <select name="checktype"><option value="">Все проверки</option>';
          $sqlRes = $mysqli->query($select);
          while($result = $sqlRes->fetch_assoc()){
              if ($result['checktype'])
                  echo '<option value="'.$result['checktype'].'"'.(isset($_REQUEST['checktype']) && $result['checktype']==$_REQUEST['checktype'] ? ' selected' : '').'>'.$result['checktype'].'</option>';
          }
          echo '</select>';
          $sqlRes->close();
//      }
      echo ' <select name="type">';
      echo '<option value="dates"'.($type=='dates'?' selected':'').'>По датам</option>';
      echo '<option value="months"'.($type=='months'?' selected':'').'>По месяцам</option>';
      echo '<option value="hours"'.($type=='hours'?' selected':'').'>По часам</option>';
      echo '<option value="minutes"'.($type=='minutes'?' selected':'').'>По минутам</option>';
      echo '<option value="sources"'.($type=='sources' || $type==''?' selected':'').'>По источникам</option>';
      echo '<option value="checktypes"'.($type=='checktypes'?' selected':'').'>По проверкам</option>';
      if ($user_area>=1) {
          echo '<option value="users"'.($type=='users'?' selected':'').'>По пользователям</option>';
      }
      if ($user_area>2) {
          echo '<option value="clients"'.($type=='clients'?' selected':'').'>По клиентам</option>';
      }
      echo '<option value="ips"'.($type=='ips'?' selected':'').'>По IP-адресам</option>';
      echo '</select>';
/*
      if ($user_level<0) {
          echo ' Поиск <input type="text" name="find" value="'.(isset($_REQUEST['find'])?$_REQUEST['find']:'').'">';
          if(isset($_REQUEST['find']) && strlen($_REQUEST['find'])){
              $conditions .= " AND locate('".mysqli_real_escape_string($mysqli,$_REQUEST['find'])."',response)>0";
          }
      }
*/
      if ($user_area>=3) {
          echo ' <select name="pay">';
          echo '<option value="all"'.(!isset($_REQUEST['pay']) || $_REQUEST['pay']=='all'?' selected':'').'>Не тарифицировать</option>';
          echo '<option value="separate"'.(isset($_REQUEST['pay']) && $_REQUEST['pay']=='separate'?' selected':'').'>Все тарифы</option>';
          echo '<option value="pay"'.(isset($_REQUEST['pay']) && $_REQUEST['pay']=='pay'?' selected':'').'>Платные</option>';
          echo '<option value="free"'.(isset($_REQUEST['pay']) && $_REQUEST['pay']=='free'?' selected':'').'>Бесплатные</option>';
          echo '<option value="test"'.(isset($_REQUEST['pay']) && $_REQUEST['pay']=='test'?' selected':'').'>Тестовые</option>';
          echo '</select>';
      }
      echo ' <select name="order">';
      echo '<option value="1"'.(!isset($_REQUEST['order']) || $_REQUEST['order']=='1'?' selected':'').'>По умолчанию</option>';
      if ($user_area>=3) {
          echo '<option value="total desc"'.(isset($_REQUEST['order']) && $_REQUEST['order']=='total desc'?' selected':'').'>По убыванию суммы</option>';
      }
      echo '<option value="reqcount desc"'.(isset($_REQUEST['order']) && $_REQUEST['order']=='reqcount desc'?' selected':'').'>По убыванию обращений</option>';
      echo '<option value="rescount desc"'.(isset($_REQUEST['order']) && $_REQUEST['order']=='rescount desc'?' selected':'').'>По убыванию запросов</option>';
      echo '</select>';
      echo ' <input type="submit" value="Обновить"></form>';

      $u = isset($_REQUEST['nested']) && $_REQUEST['nested'] && $user_area>1 && isset($_REQUEST['user_id']) && intval($_REQUEST['user_id'])==0 ? "m" : "u";
      $fields = array(
          "dates" => "r.created_date",
          "months" => "date_format(r.created_date,'%Y-%m') as month",
          "hours" => "r.hour",
          "minutes" => "r.minute",
          "sources" => "r.source_name",
          "params" => "substring(start_param,1,locate('[',concat(start_param,'['))-1) as start_param",
          "users" => "$u.login",
          "clients" => "c.code",
          "checktypes" => "r.checktype",
          "ips" => "req.ip",
      );

if (!$type) {
} elseif (!isset($fields[$type])) {
      echo "Неизвестный тип отчета";
} elseif (!isset($_REQUEST['from']) || !strtotime($_REQUEST['from'])) {
      echo "Укажите начальную дату";
} elseif (!isset($_REQUEST['to']) || !strtotime($_REQUEST['to'])) {
      echo "Укажите конечную дату";            
} elseif (/*$user_level>=0 && */!(isset($_REQUEST['source']) && $_REQUEST['source']) && !(isset($_REQUEST['checktype']) && $_REQUEST['checktype']) && in_array($_REQUEST['client_id'],array('','6','15','19','25','221','303')) && date('d.m.Y',strtotime($_REQUEST['from']))!=date('d.m.Y',strtotime($_REQUEST['to']))) {
      echo "Получение статистики временно ограничено только одной датой. Приносим извинения за неудобства.<br/>Если вам нужна статистика за период, напишите нам в онлайн-чат или на e-mail <a href=\"mailto:support@i-sphere.ru\">support@i-sphere.ru</a>";
} else {
      $field = $fields[$type];

      if(isset($_REQUEST['client_id']) && intval($_REQUEST['client_id']) != 0){
          $conditions .= ' AND r.client_id='.intval($_REQUEST['client_id']);
      }
      if(isset($_REQUEST['client_id']) && $_REQUEST['client_id']=='0'){
          $conditions .= ' AND r.client_id is null';
      }
      if(isset($_REQUEST['user_id']) && intval($_REQUEST['user_id']) != 0){
          $conditions .= ' AND (r.user_id='.intval($_REQUEST['user_id']);
          if (($user_area>=1) && isset($_REQUEST['nested']) && $_REQUEST['nested']) {
              $conditions .= ' OR r.user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId='.intval($_REQUEST['user_id']).')';
              if ($user_area>1)
                  $conditions .= ' OR r.user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId IN (SELECT id FROM SystemUsers WHERE MasterUserId='.intval($_REQUEST['user_id']).'))';
          }
          $conditions .= ')';
      }
      if(isset($_REQUEST['from']) && strtotime($_REQUEST['from'])){
//          $conditions .= ' AND created_date >= str_to_date(\''.date('Y-m-d',strtotime($_REQUEST['from'])).'\', \'%Y-%m-%d\')';
          $conditions .= ' AND r.created_date >= \''.date('Y-m-d',strtotime($_REQUEST['from'])).'\'';
          if (date('H:i:s',strtotime($_REQUEST['from']))>'00:00:00')
//              $conditions .= ' AND created_at >= str_to_date(\''.date('Y-m-d H:i:s',strtotime($_REQUEST['from'])).'\', \'%Y-%m-%d %H:%i:%s\')';
              $conditions .= ' AND r.created_at >= \''.date('Y-m-d H:i:s',strtotime($_REQUEST['from'])).'\'';
      }
      if(isset($_REQUEST['to']) && strtotime($_REQUEST['to'])){
//          $conditions .= ' AND created_date <= str_to_date(\''.date('Y-m-d',strtotime($_REQUEST['to'])).'\', \'%Y-%m-%d\')';
          $conditions .= ' AND r.created_date <= \''.date('Y-m-d',strtotime($_REQUEST['to'])).'\'';
          if (date('H:i:s',strtotime($_REQUEST['to']))>'00:00:00')
//              $conditions .= ' AND created_at <= str_to_date(\''.date('Y-m-d H:i:s',strtotime($_REQUEST['to'])).'\', \'%Y-%m-%d %H:%i:%s\')';
              $conditions .= ' AND r.created_at <= \''.date('Y-m-d H:i:s',strtotime($_REQUEST['to'])).'\'';
      }
      if(isset($_REQUEST['source']) && $_REQUEST['source']){
          $conditions .= ' AND r.source_name=\''.mysqli_real_escape_string($mysqli,$_REQUEST['source']).'\'';
      }
      if(isset($_REQUEST['checktype']) && $_REQUEST['checktype']){
          $conditions .= ' AND r.checktype=\''.mysqli_real_escape_string($mysqli,$_REQUEST['checktype']).'\'';
      }
      if($user_level<0 && isset($_REQUEST['ip']) && $_REQUEST['ip']){
          $rep_conditions .= ' AND req.ip=\''.mysqli_real_escape_string($mysqli,$_REQUEST['ip']).'\'';
      }
      if(isset($_REQUEST['pay'])) {
          if ($_REQUEST['pay']=='free')
              $rep_conditions .= ' AND IFNULL(u.DefaultPrice,m.DefaultPrice)=0';
          if ($_REQUEST['pay']=='pay')
              $rep_conditions .= ' AND IFNULL(u.DefaultPrice,m.DefaultPrice)>0';
          if ($_REQUEST['pay']=='test')
              $rep_conditions .= ' AND IFNULL(u.DefaultPrice,m.DefaultPrice) IS NULL';
      }
      
      $addfields = ($type=='dates'||$type=='months'||strpos($conditions,'created_date')?'created_date,':'')./*(strpos($conditions,'created_at')?'created_at,':'').*//*($type=='sources'?'source_name,':'').($type=='clients'?'client_id,':'').($type=='users'?'user_id,':'').*/($type=='checktypes'||strpos($conditions,'checktype')?'checktype,':'');
      $addgroups = $addfields;
      $addfields.= ($type=='hours'?'hour(created_at) as hour,':'');
      $addgroups.= ($type=='hours'?'hour,':'');
      $addfields.= ($type=='minutes'?'minute(created_at) as minute,':'');
      $addgroups.= ($type=='minutes'?'minute,':'');

      $viewtype = $type;
      if (isset($_REQUEST['checktype']) && $_REQUEST['checktype']) $viewtype = 'checktypes';
      if ($type!='checktypes' && isset($_REQUEST['source']) && $_REQUEST['source']) $viewtype = 'sources';

//      $tmpview = ($viewtype=='checktypes') ? 'REPORT_'.$userid.'_'.time() : false;
      $tmpview = 'REPORT_'.$userid.'_'.time();
//      $table = $tmpview ? $tmpview : (strpos('created_at',$addfiels.$conditions) || !isset($_REQUEST['to']) || !strtotime($_REQUEST['to']) ? 'RequestSource':'RequestDate');
      $table = $tmpview ? $tmpview : 'RequestSource';

if ($tmpview) {
//if ($viewtype=='clients' OR (isset($_REQUEST['client_id']) && $_REQUEST['client_id']!="") OR (isset($_REQUEST['from']) && date('Y-m-d',strtotime($_REQUEST['from']))>='2020-06-01')) {
/*
if ($viewtype!='checktypes' AND $userid==5 AND isset($_REQUEST['new'])) {
      $sql = <<<SQL
CREATE VIEW $table AS
SELECT
request_id,
user_id,
NULL client_id,
$addfields
process_time,
response_count total,
success_count,
found_count,
error_count
FROM RequestSource
WHERE response_count>0
$conditions
SQL;
*/
//      $sql = <<<SQL
//CREATE VIEW $tmpview AS
      $table = <<<SQL
(
SELECT
request_id,
source_name,
CASE WHEN created_date<'2021-01-01' THEN '' ELSE start_param END start_param,
user_id,
client_id,
$addfields
MIN(created_at) created_at,
MAX(process_time) process_time,
SUM(1) total,
SUM(res_code<>500) success_count,
SUM(res_code=200) found_count,
SUM(res_code=500) error_count
FROM ResponseNew r
WHERE res_code>0
$conditions
GROUP BY {$addgroups}
1,2,3,4,5
)
SQL;
/*
} else {
      $sql = <<<SQL
CREATE VIEW $tmpview AS
SELECT
request_id,
source_name,
user_id,
NULL client_id,
$addfields
MAX(process_time) process_time,
SUM(1) total,
SUM(res_code<>500) success_count,
SUM(res_code=200) found_count,
SUM(res_code=500) error_count
FROM Response
WHERE res_code>0
$conditions
GROUP BY {$addgroups}
request_id,source_name,user_id,client_id
SQL;
}
*/
/*
      $sql = <<<SQL
CREATE VIEW $tmpview AS
SELECT
request_id,
source_name,
user_id,
created_date,
MAX(processed_at-created_at) process_time,
SUM(1) total,
SUM(error is null) success_count,
SUM(result_count>0) found_count
FROM RequestResult
WHERE result_count IS NOT NULL
$conditions
GROUP BY 1,2,3,4
SQL;
*/

//      if ($user_level<0 && isset($_REQUEST['debug'])) {
//          echo strtr($sql."\n",array("\n"=>"<br>"))."<br><br>";
//      }
//      $mysqli->query($sql);
}

      $payfields = '';
      $field2 = '';
      $group2 = '';
      $join2 = '';

      if ($type=="users") {
          $field2 .= "$u.id user_id,";
          $group2 .= ",$u.id";
      }
      if ($type=="clients") {
          $field2 .= 'c.name,c.id client_id,';
          $group2 .= ',c.name,c.id';
      }
      if ($type=="ips" || strpos($rep_conditions,'req.ip')) {
          $group2 .= ',req.ip';
          $join2 .= 'JOIN RequestNew req ON r.request_id=req.id';
      }

      if ($type=="sources") {
//          $field .= ",".$fields["params"];
//          $group2 .= ',2';
      }

      if ((isset($_REQUEST['pay']) && ($_REQUEST['pay']=='separate' || $_REQUEST['pay']=='pay')) || (isset($_REQUEST['order']) && strpos($_REQUEST['order'],'total')!==false)) {
          if ($_REQUEST['pay']=='separate') {
              $payfields .= <<<SQL
, SUM(r.success_count>0 AND (COALESCE(u.DefaultPrice,m.DefaultPrice)=0 OR source_name IN (SELECT source_name FROM UserSourcePrice WHERE (user_id=u.id OR user_id=m.id) AND price=0))) nonpay
, SUM(r.success_count>0 AND (COALESCE(u.DefaultPrice,m.DefaultPrice) IS NULL)) test
, SUM(r.success_count>0 AND (COALESCE(u.DefaultPrice,m.DefaultPrice,0)<>0 AND source_name NOT IN (SELECT source_name FROM UserSourcePrice WHERE (user_id=u.id OR user_id=m.id) AND price=0))) pay
SQL;
          }
          $payfields .= <<<SQL
, SUM(CASE WHEN r.success_count>0 AND IFNULL(u.DefaultPrice,m.DefaultPrice)>0 THEN IFNULL((SELECT MIN(price) FROM UserSourcePrice WHERE (user_id=u.id OR user_id=m.id) AND source_name=r.source_name),IFNULL(u.DefaultPrice,m.DefaultPrice)) ELSE 0 END) total
SQL;
//          if ($type=='users' || $type=='clients' || $user_area<=1 || (isset($_REQUEST['user_id']) && $_REQUEST['user_id']) || (isset($_REQUEST['client_id']) && $_REQUEST['client_id']!="")) {
          if ($type=='sources') {
              $field2 .= 'IFNULL((SELECT MIN(price) FROM UserSourcePrice WHERE (user_id=u.id OR user_id=m.id) AND source_name=r.source_name),IFNULL(u.DefaultPrice,m.DefaultPrice)) price,';
              $group2 .= ',price';
          }
      }

      $sql = <<<SQL
SELECT
$field,
$field2
COUNT(DISTINCT r.request_id) reqcount,
COUNT(*) rescount,
SUM(r.success_count>0) success
, SUM(r.found_count>0) hit
, SUM(r.error_count>0) error
, AVG(r.process_time) process
$payfields
FROM $table r
JOIN SystemUsers u ON r.user_id=u.id
JOIN SystemUsers m ON m.id=CASE WHEN u.id=$userid OR u.MasterUserID IS NULL OR u.MasterUserID=0 OR u.AccessArea>0 THEN u.id ELSE u.MasterUserId END
$join2
LEFT JOIN Client c ON r.client_id=c.id
WHERE 1=1
$conditions
$rep_conditions
GROUP BY 1$group2
SQL;

if (isset($_REQUEST['order'])) $sql .= ' ORDER BY '.$_REQUEST['order'];

      if ($user_level<0 && isset($_REQUEST['debug'])) {
          echo strtr($sql."\n",array("\n"=>"<br>"))."<br><br>";
      }

      $title = array(
          "created_date" => "Дата",
          "month" => "Месяц",
          "hour" => "Час",
          "minute" => "Минута",
          "source_name" => "Источник",
          "start_param" => "Параметр",
          "checktype" => "Проверка",
          "login" => "Логин",
          "code" => "Код",
          "name" => "Наименование",
          "ip" => "IP",
          "reqcount" => "Обращений<br/>к сервису",
          "rescount" => "Запросов<br/>в источник",
          "success" => "Успешно<br/>обработано",
          "error" => "Ошибок",
          "nonpay" => "Бесплатные<br/>запросы",
          "test" => "Тестовые<br/>запросы",
          "pay" => "Платные<br/>запросы",
          "price" => "Цена",
          "total" => "Сумма",
          "hit" => "Найдены<br/>данные",
          "process" => "Среднее<br/>время, с",
          "successrate" => "Успешно, %",
          "hitrate" => "Найдено, %",
      );
      $hide = array(
          "dates" => array(
              "hit" => 1,
              "process" => 1,
          ),
          "months" => array(
              "hit" => 1,
              "process" => 1,
          ),
          "hours" => array(
              "hit" => 1,
              "process" => 1,
          ),
          "minutes" => array(
              "hit" => 1,
              "process" => 1,
          ),
          "sources" => array(
              "reqcount" => 1,
          ),
          "checktypes" => array(
              "reqcount" => 1,
              "pay" => 1,
              "nonpay" => 1,
              "test" => 1,
              "price" => 1,
              "total" => 1,
          ),
          "users" => array(
              "hit" => 1,
              "process" => 1,
              "user_id" => 1,
          ),
          "clients" => array(
              "hit" => 1,
              "process" => 1,
          ),
          "ips" => array(
              "hit" => 1,
              "process" => 1,
          ),
      );
      if ($payfields>"" && $viewtype!="checktypes") $hide[$viewtype]["error"] = 1;

      $total = array(
          "reqcount" => 0,
          "rescount" => 0,
          "success" => 0,
          "error" => 0,
          "pay" => 0,
          "nonpay" => 0,
          "test" => 0,
          "hit" => 0,
          "total" => 0,
      );

      $i = 0;
      $sqlRes = $mysqli->query($sql);
      echo "<table border=1>\n";
      while($result = $sqlRes->fetch_assoc()){
//              print_r($result);
                if ($i==0) {
                    $first = $result;
                    echo "<tr>\n";
                    foreach ($result as $key => $val) {
                        if (!isset($hide[$viewtype][$key]) && isset($title[$key])) {
                            echo "<th>".$title[$key]."</th>";
                        }
                    }
                    if ($viewtype=="sources" || $viewtype=="checktypes") {
                        echo "<th>".$title["successrate"]."</th>";
                        echo "<th>".$title["hitrate"]."</th>";
                    }
                    echo "</tr>\n";
                }
                echo "<tr>\n";
                foreach ($result as $key => $val) {
                    if (!isset($hide[$viewtype][$key]) && isset($title[$key])) {
                        if (isset($total[$key])) $total[$key]+=$val;
                        echo "<td ".(is_numeric($val)?"class=\"right\"":"").">";
                        $params = false;
                        if ($key=="created_date") {
                            $params = $_REQUEST;
                            $params['from']=$val;
                            $params['to']=$val;
                            $params['type']=(!isset($_REQUEST['source']) || !$_REQUEST['source']) && (!isset($_REQUEST['checktype']) || !$_REQUEST['checktype'])?'sources':(!isset($_REQUEST['user_id']) || !$_REQUEST['user_id']?'users':'hours');
                        }
                        if ($key=="hour" && isset($_REQUEST['from']) && strtotime($_REQUEST['from']) && ((isset($_REQUEST['to']) && strtotime($_REQUEST['to']) && date('d.m.Y',strtotime($_REQUEST['from']))==date('d.m.Y',strtotime($_REQUEST['to']))) || ((!isset($_REQUEST['to']) || !$_REQUEST['to']) && date('d.m.Y',strtotime($_REQUEST['from']))==date('d.m.Y')))) {
                            $params = $_REQUEST;
                            $params['from']=date('d.m.Y',strtotime($_REQUEST['from'])).' '.$val.':00:00';
                            $params['to']=date('d.m.Y',strtotime($_REQUEST['from'])).' '.$val.':59:59';
                            $params['type']=(!isset($_REQUEST['source']) || !$_REQUEST['source']) && (!isset($_REQUEST['checktype']) || !$_REQUEST['checktype'])?'sources':(!isset($_REQUEST['user_id']) || !$_REQUEST['user_id']?'users':'minutes');
                        }
                        if ($key=="minute" && isset($_REQUEST['from']) && strtotime($_REQUEST['from']) && ((isset($_REQUEST['to']) && strtotime($_REQUEST['to']) && date('d.m.Y H',strtotime($_REQUEST['from']))==date('d.m.Y H',strtotime($_REQUEST['to']))) || ((!isset($_REQUEST['to']) || !$_REQUEST['to']) && date('d.m.Y H',strtotime($_REQUEST['from']))==date('d.m.Y H')))) {
                            $params = $_REQUEST;
                            $params['from']=date('d.m.Y H',strtotime($_REQUEST['from'])).':'.$val.':00';
                            $params['to']=date('d.m.Y H',strtotime($_REQUEST['from'])).':'.$val.':59';
                            $params['type']=(!isset($_REQUEST['source']) || !$_REQUEST['source']) && (!isset($_REQUEST['checktype']) || !$_REQUEST['checktype'])?'sources':(!isset($_REQUEST['user_id']) || !$_REQUEST['user_id']?'users':'minutes');
                        }
                        if ($key=="source_name" || $key=="checktype") {
                            $params = $_REQUEST;
                            $params[$key=="source_name"?'source':'checktype']=$val;
                            $params['type']=!isset($_REQUEST['from']) || !strtotime($_REQUEST['from']) || !isset($_REQUEST['to']) || !strtotime($_REQUEST['to']) || date('d.m.Y',strtotime($_REQUEST['from']))!=date('d.m.Y',strtotime($_REQUEST['to']))?'dates':(!isset($_REQUEST['user_id']) || !$_REQUEST['user_id']?'users':'hours');
                        }
//                        if ($key=="start_param") {
//                            $val = strtr($val,array('[0]'=>'','['=>' ',']'=>''));
//                        } 
                        if ($key=="code") {
                            $params = $_REQUEST;
                            $params['client_id']=$result['client_id'];
                            $params['type']=(!isset($_REQUEST['source']) || !$_REQUEST['source']) && (!isset($_REQUEST['checktype']) || !$_REQUEST['checktype'])?'sources':(!isset($_REQUEST['from']) || !strtotime($_REQUEST['from']) || !isset($_REQUEST['to']) || !strtotime($_REQUEST['to']) || date('d.m.Y',strtotime($_REQUEST['from']))!=date('d.m.Y',strtotime($_REQUEST['to']))?'dates':(!isset($_REQUEST['user_id']) || !$_REQUEST['user_id']?'users':'hours'));
                        }
                        if ($key=="login") {
                            $params = $_REQUEST;
                            $params['user_id']=$result['user_id'];
                            $params['type']=(!isset($_REQUEST['source']) || !$_REQUEST['source']) && (!isset($_REQUEST['checktype']) || !$_REQUEST['checktype'])?'sources':(!isset($_REQUEST['from']) || !strtotime($_REQUEST['from']) || !isset($_REQUEST['to']) || !strtotime($_REQUEST['to']) || date('d.m.Y',strtotime($_REQUEST['from']))!=date('d.m.Y',strtotime($_REQUEST['to']))?'dates':'hours');
                        }
                        if ($params && ($params['type']=='hours' || $params['type']=='minutes') && isset($params['from']) && strtotime($params['from']) && ((isset($params['to']) && strtotime($params['to']) && date('d.m.Y H',strtotime($params['from']))==date('d.m.Y H',strtotime($params['to'])-1)) || (!isset($params['to']) && date('d.m.Y H',strtotime($params['from']))==date('d.m.Y H')))) {
                            unset($params['type']);
                            unset($params['pay']);
                            unset($params['order']);
                            echo '<a href="history.php?'.http_build_query($params).'">';
                        } elseif ($params)
                            echo '<a href="reports_new.php?'.http_build_query($params).'">';
                        if ($key=="process") $val = number_format($val,1,',','');
                        if (($key=="price" || $key=="total") && strlen($val)) $val = number_format($val,2,',','');
                        echo $val;
                        if ($params) echo '</a>';
                        echo "</td>";
                    }
                }
                if ($viewtype=="sources" || $viewtype=="checktypes") {
                    echo "<td class=\"right\">".($result["rescount"]>10?number_format($result["success"]/$result["rescount"]*100,2,',',''):"")."</td>";
                    echo "<td class=\"right\">".($result["success"]>10?number_format($result["hit"]/$result["success"]*100,2,',',''):"")."</td>";
                }
		echo "</tr>\n";
                $i++;
      }
      if ($i == 0) {
          echo "Нет данных";
      } else {
                foreach ($first as $key => $val) {
                    if (!isset($hide[$viewtype][$key]) && isset($title[$key])) {
                        echo "<td class=\"right\"><b>".(isset($total[$key])?$total[$key]:"")."</b></td>";
                    }
                }
                if ((isset($_REQUEST['source']) && $_REQUEST['source']) || (isset($_REQUEST['checktype']) && $_REQUEST['checktype'])) {
                    echo "<td class=\"right\"><b>".($total["rescount"]>10?number_format($total["success"]/$total["rescount"]*100,2,',',''):"")."</b></td>";
                    echo "<td class=\"right\"><b>".($total["success"]>10?number_format($total["hit"]/$total["success"]*100,2,',',''):"")."</b></td>";
                } elseif ($viewtype=="sources") {
                    echo "<td class=\"right\"></td>";
                    echo "<td class=\"right\"></td>";
                } 
      }
      $sqlRes->close();
//      if ($tmpview) $mysqli->query("DROP VIEW $tmpview");
      echo "</table><br />\n";

      $mysqli->close();
}

include('footer.php');
