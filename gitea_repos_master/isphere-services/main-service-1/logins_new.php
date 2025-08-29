<?php
      include ('config.php');
      include ('auth.php');

      $user_access = get_user_access($mysqli);
      if (!$user_access['users']) {
          echo 'У вас нет доступа к этой странице';
          exit;
      }

      set_time_limit(600);

//      $mysqli = mysqli_connect ($database['server'],$database['login'],$database['password'], $database['name']);

      echo '<link rel="stylesheet" type="text/css" href="main.css"/>';
      echo '<h1>Информация по логинам</h1><hr/><a href="admin.php">Назад</a><br/><br/>';

      $type = isset($_REQUEST['type'])?$_REQUEST['type']:'';

      $userid = get_user_id($mysqli);
      $user_level = get_user_level($mysqli);
      $user_area = get_user_area($mysqli);
      $clientid = get_client_id($mysqli);
      $conditions = '';
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
      if ($user_area>=2) {
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
              $conditions .= ' AND Id IN ('.$users_list.')';
          }
      } else {
          $_REQUEST['user_id'] = $userid;
      }
      $sqlRes->close();
      echo $users;
      if ($user_area>=2) {
          echo ' <input type="checkbox" name="locked"'.(isset($_REQUEST['locked']) && $_REQUEST['locked']?' checked="checked"':'').'>+заблокированные';
      }

      echo ' <select name="pay">';
      echo '<option value="all"'.(isset($_REQUEST['pay']) && $_REQUEST['pay']=='separate'?' selected':'').'>Все тарифы</option>';
      echo '<option value="pay"'.(isset($_REQUEST['pay']) && $_REQUEST['pay']=='pay'?' selected':'').'>Платные</option>';
      echo '<option value="free"'.(isset($_REQUEST['pay']) && $_REQUEST['pay']=='free'?' selected':'').'>Бесплатные</option>';
      echo '<option value="test"'.(isset($_REQUEST['pay']) && $_REQUEST['pay']=='test'?' selected':'').'>Тестовые</option>';
      echo '</select>';

      $select = "SELECT Level, Name FROM isphere.Access ORDER BY Name";
      $sqlRes = $mysqli->query($select);
      if ($sqlRes->num_rows>1) {
          $access = '<select name="access"><option value="">Все доступы</option>';
          while($result = $sqlRes->fetch_assoc()){
              $access .= '<option value="'.$result['Level'].'"'.(isset($_REQUEST['access']) && $result['Level']==$_REQUEST['access'] ? ' selected' : '').'>'.$result['Name'].'</option>';
          }
          $access .= '</select>';
      }
      $sqlRes->close();
      echo $access;

      echo ' <select name="order">';
      echo '<option value="login"'.(!isset($_REQUEST['order']) || $_REQUEST['order']=='login'?' selected':'').'>По логину</option>';
      echo '<option value="created"'.(isset($_REQUEST['order']) && $_REQUEST['order']=='created'?' selected':'').'>По дате создания</option>';
      echo '<option value="lastused"'.(isset($_REQUEST['order']) && $_REQUEST['order']=='lastused'?' selected':'').'>По дате последнего запроса</option>';
      echo '</select>';
      echo ' <input type="submit" value="Обновить"></form>';

      if(isset($_REQUEST['client_id']) && intval($_REQUEST['client_id']) != 0){
          $conditions .= ' AND ClientId='.intval($_REQUEST['client_id']);
      }
      if(isset($_REQUEST['client_id']) && $_REQUEST['client_id']=='0'){
          $conditions .= ' AND ClientId is null';
      }
      if(isset($_REQUEST['user_id']) && intval($_REQUEST['user_id']) != 0){
          $conditions .= ' AND (Id='.intval($_REQUEST['user_id']);
          if ($user_area>=1) {
              $conditions .= ' OR Id IN (SELECT Id FROM SystemUsers WHERE MasterUserId='.intval($_REQUEST['user_id']).')';
              if ($user_area>1)
                  $conditions .= ' OR Id IN (SELECT Id FROM SystemUsers WHERE MasterUserId IN (SELECT id FROM SystemUsers WHERE MasterUserId='.intval($_REQUEST['user_id']).'))';
          }
          $conditions .= ')';
      }
      if(isset($_REQUEST['locked']) && $_REQUEST['locked']){
      } else {
          $conditions .= ' AND (u.Locked is null OR u.Locked=0)';
      }
      if(isset($_REQUEST['access']) && $_REQUEST['access'] != ''){
          $conditions .= ' AND u.AccessLevel='.intval($_REQUEST['access']);
      }
      if(isset($_REQUEST['pay'])) {
          if ($_REQUEST['pay']=='free')
              $conditions .= ' AND IFNULL(u.DefaultPrice,m.DefaultPrice)=0';
          if ($_REQUEST['pay']=='pay')
              $conditions .= ' AND IFNULL(u.DefaultPrice,m.DefaultPrice)>0';
          if ($_REQUEST['pay']=='test')
              $conditions .= ' AND IFNULL(u.DefaultPrice,m.DefaultPrice) IS NULL';
      }
      
      $sql = <<<SQL
SELECT
u.Login,u.Name,u.OrgName,u.Phone,u.Email,date(u.Created) Created,
(SELECT MAX(created_date) FROM RequestNew WHERE user_id=u.Id) LastUsed,
a.Name AccessName,IFNULL(u.DefaultPrice,m.DefaultPrice) Price,
u.Id
FROM SystemUsers u
JOIN SystemUsers m ON m.id=CASE WHEN u.id=$userid OR u.MasterUserID IS NULL OR u.AccessArea>0 THEN u.id ELSE u.MasterUserId END
JOIN Access a ON a.Level=u.AccessLevel
LEFT JOIN Client c ON u.ClientId=c.id
WHERE 1=1
$conditions
SQL;

if (!isset($_REQUEST['order'])) {
    $_REQUEST['order'] = 'login';
}
$sql .= ' ORDER BY '.$_REQUEST['order'];

      if ($userid==5 && isset($_REQUEST['debug'])) {
          echo strtr($sql."\n",array("\n"=>"<br>"))."<br><br>";
      }

      $title = array(
          "Login" => "Логин",
          "Name" => "Имя",
          "OrgName" => "Организация",
          "Phone" => "Телефон",
          "Email" => "Email",
          "Price" => "Стоимость",
          "Created" => "Дата создания",
          "LastUsed" => "Последний запрос",
          "AccessName" => "Доступ",
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
                        if (isset($title[$key])) {
                            echo "<th>".$title[$key]."</th>";
                        }
                    }
                }
		echo "</tr>\n";
                echo "<tr>\n";
                foreach ($result as $key => $val) {
                    if (isset($title[$key])) {
                        echo "<td ".(is_numeric($val)?"class=\"right\"":"").">";
                        $a = false;
                        if ($key=="Login") {
                            $params = array();
                            $params['user_id']=$result['Id'];
                            $params['type'] = 'months';
                            $params['from'] = $result['Created'];
                            echo '<a href="reports_new.php?'.http_build_query($params).'">';
                            $a = true;
                        }
                        if (($key=="price") && strlen($val)) $val = number_format($val,2,',','');
                        echo $val;
                        if ($a) echo '</a>';
                        echo "</td>";
                    }
                }
		echo "</tr>\n";
                $i++;
      }
      if ($i == 0) {
          echo "Нет данных";
      }
      $sqlRes->close();
      echo "</table><br />\n";

      $mysqli->close();


include('footer.php');
