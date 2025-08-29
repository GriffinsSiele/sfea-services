<?php
      include ('config.php');
      include ('auth.php');

      $user_access = get_user_access($mysqli);
      if (!$user_access['bulk']) {
          echo 'У вас нет доступа к этой странице';
          exit;
      }

//      $mysqli = mysqli_connect ($database['server'],$database['login'],$database['password'], $database['name']);

      echo '<link rel="stylesheet" type="text/css" href="main.css"/>';
      echo '<meta http-equiv="Refresh" content="30"/>';
      echo '<h1>Реестры на обработку</h1><hr/><a href="admin.php">Назад</a><br/><br/>';

      if (!isset($_REQUEST['from'])) $_REQUEST['from']=date('01.m.Y'); //date('d.m.Y');

      $userid = get_user_id($mysqli);
      $user_level = get_user_level($mysqli);
      $user_area = get_user_area($mysqli);
      $conditions = '';
      $join = '';
      $order = 'r.id DESC';
      $limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 20;
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
          }
      }
      $select .= " ORDER BY Login";
      $sqlRes = $mysqli->query($select);
      if ($sqlRes->num_rows>1) {
          $users = ' <select name="user_id"><option value="">Все пользователи</option>';
          while($result = $sqlRes->fetch_assoc()){
              $users .= '<option value="'.$result['Id'].'"'.(isset($_REQUEST['user_id']) && $result['Id']==$_REQUEST['user_id'] ? ' selected' : '').'>'.$result['Login'].($result['Locked']?' (-)':'').'</option>';
              $users_list .= ($users_list?',':'').$result['Id'];
          }
          $users .= '</select>';
          if ($user_area<=2) {
              $conditions .= ' AND r.user_id IN ('.$users_list.')';
          }
      } else {
          $_REQUEST['user_id'] = $userid;
      }
      $sqlRes->close();
//      if ($users || ($user_level<0)) {
          echo '<form action="">'.$users;
          if ($user_area>=2) {
              echo ' <input type="checkbox" name="nested"'.(isset($_REQUEST['nested']) && $_REQUEST['nested']?' checked="checked"':'').'>+дочерние';
              if (isset($_REQUEST['limit']) && $limit) {
                  echo ' <input type="hidden" name="limit" value="'.$limit.'">';
              }
          }
//          if ($user_level<0) {
              echo ' Период с <input type="text" name="from" value="'.(isset($_REQUEST['from'])?$_REQUEST['from']:'').'"> по <input type="text" name="to" value="'.(isset($_REQUEST['to'])?$_REQUEST['to']:'').'">';
//          }
          if ($user_level<0) {
              $select = "SELECT DISTINCT source_name FROM isphere.ResponseNew ORDER BY 1";
              echo ' <select name="source"><option value="">Все источники</option>';
              $sqlRes = $mysqli->query($select);
              while($result = $sqlRes->fetch_assoc()){
                  echo '<option value="'.$result['source_name'].'"'.(isset($_REQUEST['source']) && $result['source_name']==$_REQUEST['source'] ? ' selected' : '').'>'.$result['source_name'].'</option>';
              }
              echo '</select>';
              $sqlRes->close();
          }
          if ($user_level<0) {
              echo ' <select name="status">';
              echo '<option value=""'.(!isset($_REQUEST['status']) || !$_REQUEST['status']?' selected':'').'>Все статусы</option>';
              echo '<option value="0"'.(isset($_REQUEST['status']) && $_REQUEST['status']=='0'?' selected':'').'>Обрабатывается</option>';
              echo '<option value="1"'.(isset($_REQUEST['status']) && $_REQUEST['status']=='1'?' selected':'').'>Обработан</option>';
              echo '<option value="2"'.(isset($_REQUEST['status']) && $_REQUEST['status']=='1'?' selected':'').'>В ожидании</option>';
              echo '<option value="3"'.(isset($_REQUEST['status']) && $_REQUEST['status']=='1'?' selected':'').'>Не обработан</option>';
              echo '<option value="-1"'.(isset($_REQUEST['status']) && $_REQUEST['status']=='-1'?' selected':'').'>Удален</option>';
              echo '</select>';
          }
          echo ' <input type="submit" value="Обновить"></form>';
//      }

      if(isset($_REQUEST['user_id']) && intval($_REQUEST['user_id']) != 0){
          $conditions .= ' AND (r.user_id='.intval($_REQUEST['user_id']).(isset($_REQUEST['nested']) && $_REQUEST['nested']?' OR r.user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId='.intval($_REQUEST['user_id']).')':'').')';
      }
      if(isset($_REQUEST['from']) && strtotime($_REQUEST['from'])){
          $conditions .= ' AND r.created_at >= str_to_date(\''.date('Y-m-d',strtotime($_REQUEST['from'])).' 00:00:00\', \'%Y-%m-%d %H:%i:%s\')';
      }
      if(isset($_REQUEST['to']) && strtotime($_REQUEST['to'])){
          $conditions .= ' AND r.created_at <= str_to_date(\''.date('Y-m-d',strtotime($_REQUEST['to'])).' 23:59:59\', \'%Y-%m-%d %H:%i:%s\')';
      }
      if(isset($_REQUEST['minid'])){
          $conditions .= ' AND r.id < '.intval($_REQUEST['minid']);
      }
      if(isset($_REQUEST['maxid'])){
          $conditions .= ' AND r.id > '.intval($_REQUEST['maxid']);
      }
      if(isset($_REQUEST['source']) && $_REQUEST['source']){
          $conditions .= ' AND sources LIKE \'%'.mysqli_real_escape_string($mysqli,$_REQUEST['source']).'%\'';
      }
      if(isset($_REQUEST['status']) && preg_match("/^[-]?[\d]+$/",$_REQUEST['status'])){
          $conditions .= ' AND r.status = '.intval($_REQUEST['status']);
      } else {
          $conditions .= ' AND r.status >= 0';
      }
      
      $select = "SELECT r.*,u.login FROM Bulk r, SystemUsers u $join WHERE r.user_id=u.Id $conditions ORDER BY $order LIMIT $limit";
      $sqlRes = $mysqli->query($select);
      $minid = 1000000000;
      $maxid = 0;
 
      $bulk_status = array('Обрабатывается','Обработан','В ожидании','Не обработан');
      $bulk_status[-1] = 'Удален';
      echo "<table border=1>\n";
      while($result = $sqlRes->fetch_assoc()){
                if($maxid < $result['id']){
		        $maxid = $result['id'];
		}
		if( $minid > $result['id'] ){
		        $minid = $result['id'];
		}
//              print_r($result);
                echo "<tr>\n";
                echo "<td>".($user_area>0 ? '<a href="bulkrequest.php?id='.$result['id'].'" target=_blank>':'').$result['filename'].($user_area>0 ? '</a>':'')."</td>\n";
		echo "<td>".$result['created_at']."</td>\n";
		echo "<td>".$result['processed_at']."</td>\n";
		echo "<td>".$result['login']."</td>\n";
//		echo "<td>".$result['ip']."</td>\n";
		echo "<td>".( isset($result['sources']) ? strtr($result['sources'],array(','=>'<br/>')) : '' )."</td>\n";
		echo "<td>".( isset($result['processed_rows']) ? $result['processed_rows'] : (isset($result['total_rows']) ? $result['total_rows'] : ""))."</td>\n";
		echo "<td>".($result['status']==0 || $result['created_at']>='2024-04-01' ? '<a href="bulkinfo.php?id='.$result['id'].'">':'').$bulk_status[$result['status']].($result['status']==0 ? '</a>':'')."</td>\n";
		echo "<td>".($result['status']==1 && isset($result['results_note']) && preg_match("/Не удалось обработать часть запросов/",$result['results_note'])?'<a href="bulkinfo.php?id='.$result['id'].'">'.$result['results_note'].'</a>':(isset($result['results_note'])?$result['results_note']:''))."</td>\n";
		echo "<td>".($result['status']==1 ? '<a href="bulkresult.php?id='.$result['id'].'" target=_blank>Скачать результаты</a><br/>' : '') . "</td>\n";
		echo "</tr>\n";		
      }
      $sqlRes->close();
      echo "</table><br/>\n";
      echo '<b>Теперь пакетная обработка запросов во все источники производится в автоматическом режиме!</b><br/><br/>';
/*
      echo '<b>Теперь пакетная обработка запросов в ФНС (поиск ИНН), ФССП, ЕФРСБ (банкроты), КАД (арбитражный суд) и Нотариат (наследственные дела) производится полностью в автоматическом режиме!</b><br/>';
      echo '<a href="bulkAuto/stepOne.php">Загрузить новый реестр на обработку по источникам ФНС (поиск ИНН), ФССП, ЕФРСБ (банкроты), КАД (арбитражный суд), Нотариат (наследственные дела)</a><br/><br/>';
      echo '<b>Пакетная обработка запросов в остальные источники производится по-прежнему в ручном режиме нашими специалистами</b><br/>';
*/
      echo '<a href="bulkAuto/stepOne.php">Загрузить новый реестр на обработку</a><br/>';
      echo "<br/>\n";
      $mysqli->close();
      $querystr = preg_replace( "/\&m(in|ax)id=\d+/", "", getenv('QUERY_STRING') );
      echo '<a href="bulk.php?'.$querystr.'&maxid='.$maxid.'"> << </a> ';
      echo '<a href="bulk.php?'.$querystr.'&minid='.$minid.'"> >> </a>';


include('footer.php');
