<?php
      include ('config.php');
      include ('auth.php');

      $user_access = get_user_access($mysqli);
      if (!$user_access['history']) {
          echo 'У вас нет доступа к этой странице';
          exit;
      }

      echo '<link rel="stylesheet" type="text/css" href="main.css"/>';
      echo '<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css">';
      echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>';
      echo '<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>';

      $user_message = get_user_message($mysqli);
      if ($user_message) {
          echo '<span class="message">'.$user_message.'</span><hr/>';
      }

      echo '<h1>История запросов</h1><hr/><a href="admin.php">Назад</a><br/><br/>';

      if (!isset($_REQUEST['from'])) $_REQUEST['from']=/*date('01.m.Y'); /*/date('d.m.Y');

      $userid = get_user_id($mysqli);
      $user_level = get_user_level($mysqli);
      $user_area = get_user_area($mysqli);
      $clientid = get_client_id($mysqli);

      $conditions = '';
      $join = '';
      $order = $user_area>=4 && isset($_REQUEST['order']) ? mysqli_real_escape_string($mysqli,$_REQUEST['order']) : 'id DESC';
      $limit = $user_area>=4 && isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 20;
      $users = '';
      $users_list = '';

      echo '<form action="">';

      $select = "SELECT Id, Code FROM isphere.Client";
      if ($user_area<4) {
          $select .= " WHERE Id=$clientid";
          if ($user_area>=3) {
              $select .= " OR MasterUserId=$userid";
          }
      }
      $select .= " ORDER BY Code";
      $sqlRes = $mysqli->query($select);
      if ($user_area>=3 && $sqlRes->num_rows>0) {
          $clients = '<select name="client_id"><option value="">Все клиенты</option>';
          $clients .= '<option value="0"'.(isset($_REQUEST['client_id']) && $_REQUEST['client_id']==="0" ? ' selected' : '').'>Без договора</option>';
          while($result = $sqlRes->fetch_assoc()){
              $clients .= '<option value="'.$result['Id'].'"'.(isset($_REQUEST['client_id']) && $result['Id']==$_REQUEST['client_id'] ? ' selected' : '').'>'.$result['Code'].'</option>';
          }
          $clients .= '</select>';
      }
      $sqlRes->close();
      if ($user_area>=3) {
          echo $clients;
      } else {
          $_REQUEST['client_id'] = $clientid;
      }

      $select = "SELECT Id, Login, Locked FROM isphere.SystemUsers";
      if ($user_area<4) {
          $select .= " WHERE Id=$userid";
          if ($user_area>=1) {
              $select .= " OR MasterUserId=$userid";
          }
          if ($user_area>=2) {
              $select .= " OR ClientId=$clientid";
          }
          if ($user_area>=3) {
              $select .= " OR ClientId IN (SELECT id FROM Client WHERE MasterUserId=$userid)";
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
          if ($user_area<4) {
              $conditions .= ' AND user_id IN ('.$users_list.')';
          }
      } else {
          $_REQUEST['user_id'] = $userid;
      }
      $sqlRes->close();

//      if ($users || ($user_level<0)) {
          echo $users;
          if ($user_area>=2) {
              echo ' <input type="checkbox" name="nested"'.(isset($_REQUEST['nested']) && $_REQUEST['nested']?' checked="checked"':'').'>+дочерние';
              if (isset($_REQUEST['limit']) && $limit) {
                  echo ' <input type="hidden" name="limit" value="'.$limit.'">';
              }
          }
//          if ($user_level<0) {
              echo ' Период с <input type="text" name="from" value="'.(isset($_REQUEST['from'])?$_REQUEST['from']:'').'"> по <input type="text" name="to" value="'.(isset($_REQUEST['to'])?$_REQUEST['to']:'').'">';
//          }
//          if ($user_level<0) {
              $select = "SELECT DISTINCT source_name FROM isphere.ResponseNew ORDER BY 1";
              echo ' <select name="source"><option value="">Все источники</option>';
              $sqlRes = $mysqli->query($select);
              while($result = $sqlRes->fetch_assoc()){
                  echo '<option value="'.$result['source_name'].'"'.(isset($_REQUEST['source']) && $result['source_name']==$_REQUEST['source'] ? ' selected' : '').'>'.$result['source_name'].'</option>';
              }
              echo '</select>';
              $sqlRes->close();
//          }
//          if ($user_level<0) {
              $select = "SELECT DISTINCT checktype FROM isphere.ResponseNew ORDER BY 1";
              echo ' <select name="checktype"><option value="">Все проверки</option>';
              $sqlRes = $mysqli->query($select);
              while($result = $sqlRes->fetch_assoc()){
                  if ($result['checktype'])
                      echo '<option value="'.$result['checktype'].'"'.(isset($_REQUEST['checktype']) && $result['checktype']==$_REQUEST['checktype'] ? ' selected' : '').'>'.$result['checktype'].'</option>';
              }
              echo '</select>';
              $sqlRes->close();
//          }
          if ($user_level<0) {
              echo ' <select name="res_code">';
              echo '<option value=""'.(!isset($_REQUEST['res_code']) || !$_REQUEST['res_code']?' selected':'').'>Все результаты</option>';
              echo '<option value="200"'.(isset($_REQUEST['res_code']) && $_REQUEST['res_code']=='200'?' selected':'').'>Найден</option>';
              echo '<option value="204"'.(isset($_REQUEST['res_code']) && $_REQUEST['res_code']=='204'?' selected':'').'>Не найден</option>';
              echo '<option value="500"'.(isset($_REQUEST['res_code']) && $_REQUEST['res_code']=='500'?' selected':'').'>Ошибка</option>';
              echo '</select>';
          }
          if ($user_level<0) {
              echo ' <select name="status">';
              echo '<option value=""'.(!isset($_REQUEST['status']) || !$_REQUEST['status']?' selected':'').'>Все статусы</option>';
              echo '<option value="0"'.(isset($_REQUEST['status']) && $_REQUEST['status']=='0'?' selected':'').'>Выполняется</option>';
              echo '<option value="1"'.(isset($_REQUEST['status']) && $_REQUEST['status']=='1'?' selected':'').'>Выполнен</option>';
              echo '<option value="-1"'.(isset($_REQUEST['status']) && $_REQUEST['status']=='-1'?' selected':'').'>Просрочен</option>';
              echo '</select>';
          }
          if ($user_level<0) {
              echo ' <select name="reqtype">';
              echo '<option value=""'.(!isset($_REQUEST['reqtype']) || !$_REQUEST['reqtype']?' selected':'').'>Все типы</option>';
              echo '<option value="api"'.(isset($_REQUEST['reqtype']) && $_REQUEST['reqtype']=='api'?' selected':'').'>API</option>';
              echo '<option value="bulk"'.(isset($_REQUEST['reqtype']) && $_REQUEST['reqtype']=='bulk'?' selected':'').'>Реестр</option>';
              echo '<option value="check"'.(isset($_REQUEST['reqtype']) && $_REQUEST['reqtype']=='check'?' selected':'').'>Форма</option>';
              echo '</select>';
          }
/*
          if ($user_level<0) {
              echo ' Поиск <input type="text" name="find" value="'.(isset($_REQUEST['find'])?$_REQUEST['find']:'').'">';
          }
*/
          echo ' <input type="submit" value="Обновить"></form>';
//      }
/*
      if(isset($_REQUEST['find']) && strlen($_REQUEST['find'])){
          $conditions .= " AND locate('".mysqli_real_escape_string($mysqli,$_REQUEST['find'])."',r.request)>0";
      }
*/
      if(isset($_REQUEST['user_id']) && intval($_REQUEST['user_id']) != 0){
          $conditions .= ' AND (user_id='.intval($_REQUEST['user_id']).(isset($_REQUEST['nested']) && $_REQUEST['nested']?' OR user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId='.intval($_REQUEST['user_id']).')':'').')';
      }
      if(isset($_REQUEST['client_id']) && intval($_REQUEST['client_id']) != 0){
          $conditions .= ' AND client_id='.intval($_REQUEST['client_id']);
      }
      if(isset($_REQUEST['client_id']) && $_REQUEST['client_id']=='0'){
          $conditions .= ' AND client_id is null';
      }
/*
      if(isset($_REQUEST['from']) && preg_match("/^201\d\-[01]\d\-[0-3]\d$/", $_REQUEST['from'])){
            if(isset($_REQUEST['to']) && preg_match("/^201\d\-[01]\d\-[0-3]\d$/", $_REQUEST['to'])){
                    $conditions .= ' AND r.created_at >= \''.$_REQUEST['from'].' 00:00:00\' AND r.created_at <= \''.$_REQUEST['to'].' 23:59:59\'';
	    }
	    else{
	            $conditions .= ' AND r.created_at LIKE  \''.$_REQUEST['from'].'%\'';
	    }
      }
*/
      if(isset($_REQUEST['from']) && strtotime($_REQUEST['from'])){
          $conditions .= ' AND created_date >= str_to_date(\''.date('Y-m-d',strtotime($_REQUEST['from'])).'\', \'%Y-%m-%d\')';
          if (date('H:i:s',strtotime($_REQUEST['from']))>'00:00:00')
              $conditions .= ' AND created_at >= str_to_date(\''.date('Y-m-d H:i:s',strtotime($_REQUEST['from'])).'\', \'%Y-%m-%d %H:%i:%s\')';
      }
      if(isset($_REQUEST['to']) && strtotime($_REQUEST['to'])){
          $conditions .= ' AND created_date <= str_to_date(\''.date('Y-m-d',strtotime($_REQUEST['to'])).'\', \'%Y-%m-%d\')';
          if (date('H:i:s',strtotime($_REQUEST['to']))>'00:00:00')
              $conditions .= ' AND created_at <= str_to_date(\''.date('Y-m-d H:i:s',strtotime($_REQUEST['to'])).'\', \'%Y-%m-%d %H:%i:%s\')';
      }
      if(isset($_REQUEST['minid'])){
          $conditions .= ' AND id < '.intval($_REQUEST['minid']);
      }
      if(isset($_REQUEST['maxid'])){
          $conditions .= ' AND id > '.intval($_REQUEST['maxid']);
      }
      if($user_level<0 && isset($_REQUEST['ip']) && preg_match("/^((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]?)$/",$_REQUEST['ip'])){
          $conditions .= ' AND ip =\''.mysqli_real_escape_string($mysqli,$_REQUEST['ip']).'\'';
      }
      if($user_level<0 && isset($_REQUEST['status']) && $_REQUEST['status']!=''){
          $conditions .= ' AND status = '.intval($_REQUEST['status']);
      }
      if($user_level<0 && isset($_REQUEST['reqtype']) && ($_REQUEST['reqtype']=='api' || $_REQUEST['reqtype']=='bulk' || $_REQUEST['reqtype']=='check')){
          $conditions .= ' AND type '.($_REQUEST['reqtype']=='api'?'IS NULL':($_REQUEST['reqtype']=='bulk'?' = "bulk"':'LIKE "check%"'));
      }

      $response_conditions = '';
      if(isset($_REQUEST['source']) && $_REQUEST['source']){
          $response_conditions .= ' AND res_code>0 AND source_name =\''.mysqli_real_escape_string($mysqli,$_REQUEST['source']).'\'';
      }
      if(isset($_REQUEST['checktype']) && $_REQUEST['checktype']){
          $response_conditions .= ' AND res_code>0 AND checktype =\''.mysqli_real_escape_string($mysqli,$_REQUEST['checktype']).'\'';
      }
      if($user_level<0 && isset($_REQUEST['res_code']) && intval($_REQUEST['res_code'])){
//          if((isset($_REQUEST['source']) && $_REQUEST['source']) || (isset($_REQUEST['checktype']) && $_REQUEST['checktype'])){
              $response_conditions .= ' AND res_code='.intval($_REQUEST['res_code']);
//          }
      }
      if($user_level<0 && isset($_REQUEST['slower']) && intval($_REQUEST['slower'])){
//          if((isset($_REQUEST['source']) && $_REQUEST['source']) || (isset($_REQUEST['checktype']) && $_REQUEST['checktype'])){
              $response_conditions .= ' AND process_time>'.intval($_REQUEST['slower']);
//          }
      }
      if ($response_conditions)
          $conditions .= " AND r.id IN (SELECT request_id id FROM ResponseNew WHERE 1=1 ".strtr($conditions,array('AND id'=>'AND request_id'))." $response_conditions)";
      
//      $select = "SELECT r.*,u.login FROM RequestNew r, SystemUsers u $join WHERE r.user_id=u.Id $conditions ORDER BY $order LIMIT $limit";
      $select = "SELECT r.*,(SELECT Login FROM SystemUsers WHERE id=r.user_id) login FROM RequestNew r WHERE 1=1 $conditions ORDER BY $order LIMIT $limit";
//      echo "$select<br/><br/>";
      $sqlRes = $mysqli->query($select);
      $minid = isset($_REQUEST['minid'])?$_REQUEST['minid']:1000000000;
      $maxid = isset($_REQUEST['maxid'])?$_REQUEST['maxid']:0;
      if (!$sqlRes) 
          echo "Ошибка при выполнении запроса\n";
      elseif ($sqlRes->num_rows)
          echo "<table border=1>\n";
      else
          echo "Запросов не найдено\n";
      file_put_contents('logs/history.csv',"id;request_id;crated_at;login;type;ip;sources;request_data;result_url\n");
      while($sqlRes && ($result = $sqlRes->fetch_assoc())){
                if($maxid < $result['id']){
		        $maxid = $result['id'];
		}
		if( $minid > $result['id'] ){
		        $minid = $result['id'];
		}
//              print_r($result);
                echo "<tr>\n"; $row = array();
                echo "<td>".$result['id']."</td>"; $row[] = $result['id'];
                echo "<td>".$result['external_id']."</td>"; $row[] = $result['external_id'];
		echo "<td>".$result['created_at']."</td>"; $row[] = $result['created_at'];
                $delim = '<br/>';
                $datadelim = ' ';
                $result['request'] = '';
                $numName = str_pad($result['id'], 9, '0', STR_PAD_LEFT);
                $titles = str_split($numName, 3);
                if(file_exists('/opt/xml/'.$titles[0].'/'.$titles[1].'/'.$titles[2].'_req.xml')){
                    $result['request'] = file_get_contents('/opt/xml/'.$titles[0].'/'.$titles[1].'/'.$titles[2].'_req.xml');
                }elseif(file_exists('/opt/xml/'.$titles[0].'/'.$titles[1].'.tar.gz')){
                    $result['request'] = shell_exec('tar xzfO /opt/xml/'.$titles[0].'/'.$titles[1].'.tar.gz '.$titles[2].'_req.xml');
                }
                $result['request'] = preg_replace("/<\?xml[^>]+>/", "", substr($result['request'],strpos($result['request'],'<')));
                $result['request'] = preg_replace("/<[\/]*root>/", "", $result['request']);
		$libxml_previous_state = libxml_use_internal_errors(true);
		$request = simplexml_load_string($result['request']);
		libxml_clear_errors();
		libxml_use_internal_errors($libxml_previous_state);
                if(isset($request->Request)) $request=$request->Request;
		echo "<td>".$result['login']."</td>\n"; $row[] = $result['login'];
		echo "<td>".($result['type']?$result['type']:'api')."</td>\n"; $row[] = $result['type']?$result['type']:'api';
		echo "<td>".$result['ip']."</td>\n"; $row[] = $result['ip'];
		echo "<td>".( isset($request->sources) ? strtr($request->sources,array(' '=>'',' '=>'',','=>'<br/>')) : (isset($request->PersonReq->sources) ? strtr($request->PersonReq->sources,array(','=>'<br/>')) : '' ) )."</td>\n"; $request->sources;
//		echo "<td>".( isset($request->sources) ? strtr($request->sources,array(' '=>'',' '=>'',/*','=>$delim*/)) : (isset($request->PersonReq->sources) ? strtr($request->PersonReq->sources,array(/*','=>$delim*/)) : '' ) )."</td>\n"; $row[] = $request->sources;
		echo "<td>"; $data = "";
                     if(!$request) {
                         echo "Данные запроса недоступны"; $data = "Данные запроса недоступны";
                     }
		     if(isset($request->PersonReq)){
		           $prequest = json_decode(json_encode($request->PersonReq), true); 
		           foreach($prequest as $key => $val){
			         if($val && !is_array($val) && !in_array($key, array('UserID','Password','requestId','sources','issueDate','issueAuthority'))){
			                echo $key.": ".$val.$delim; $data .= $val.$datadelim;
				 }
			   }
		     }
		     if(isset($request->PhoneReq)){
		           foreach($request->PhoneReq as $req){
		                    echo $req->phone.$delim; $data .= $req->phone.$datadelim;
			   }
		     }
		     if(isset($request->EmailReq)){
		           foreach($request->EmailReq as $req){
		                    echo $req->email.$delim; $data .= $req->email.$datadelim;
			   }
		     }
		     if(isset($request->SkypeReq)){
		           foreach($request->SkypeReq as $req){
		                    echo $req->skype.$delim; $data .= $req->skype.$datadelim;
			   }
		     }
		     if(isset($request->NickReq)){
		           foreach($request->NickReq as $req){
		                    echo $req->nick."<br />"; $data .= $req->nick.$datadelim;
			   }
		     }
		     if(isset($request->URLReq)){
		           foreach($request->URLReq as $req){
		                    echo $req->url.$delim; $data .= $req->url.$datadelim;
			   }
		     }
		     if(isset($request->CarReq)){
		           $prequest = json_decode(json_encode($request->CarReq), true); 
		           foreach($prequest as $key => $val){
		                if ($val && !is_array($val)) { echo $key.": ".$val.$delim; $data .= $val.$datadelim; }
			   }
		     }
		     if(isset($request->IPReq)){
		           foreach($request->IPReq as $req){
		                    echo $req->ip.$delim; $data .= $req->ip.$datadelim;
			   }
		     }
		     if(isset($request->OrgReq)){
		           $prequest = json_decode(json_encode($request->OrgReq), true); 
		           foreach($prequest as $key => $val){
			        if($val && !is_array($val)) { echo $key.": ".$val.$delim; $data .= $val.$datadelim; }
			   }
		     }
		     if(isset($request->OtherReq)){
		           $prequest = json_decode(json_encode($request->OtherReq), true); 
		           foreach($prequest as $key => $val){
		                if ($val && !is_array($val)) { echo $key.": ".$val.$delim; $data .= $val.$datadelim; }
			   }
		     }
		     if(isset($request->CardReq)){
		           foreach($request->CardReq as $req){
		                    echo $req->card.$delim; $data .= $req->card.$datadelim;
			   }
		     }
		echo "</td>"; $row[] = '"'.trim($data).'"';
//		$response = simplexml_load_string($result['response']);
		echo "<td>";
                if ($request) {
		    echo '<a href="showresult_new.php?id='.$result['id'].'" target=_blank>Просмотр</a>'.$delim.'<a href="showresult_new.php?id='.$result['id'].'&mode=pdf" target=_blank>PDF</a>&nbsp;<a href="showresult_new.php?id='.$result['id'].'&mode=xml" target=_blank>XML</a>';
                    global $serviceurl;
                    $row[] = $serviceurl.'showresult_new.php?id='.$result['id'];
                } else {
		    echo "Результаты обработки отсутствуют"; $row[] = "Результаты обработки отсутствуют";
                }
		echo "</td>";
		echo "</tr>\n";
                file_put_contents('logs/history.csv',implode(';',$row)."\n",FILE_APPEND);
      }
      if ($sqlRes && $sqlRes->num_rows) {
          echo "</table>\n";
      }
      echo "<br />\n";
      $querystr = preg_replace( "/\&m(in|ax)id=\d+/", "", getenv('QUERY_STRING') );
      echo '<a href="history_new.php?'.$querystr.'&maxid='.($maxid?$maxid:'').'"> << </a> ';
      if ($sqlRes && ($sqlRes->num_rows==$limit))
          echo '<a href="history_new.php?'.$querystr.'&minid='.$minid.'"> >> </a>';
      if ($sqlRes) $sqlRes->close();
      $mysqli->close();

include('footer.php');
