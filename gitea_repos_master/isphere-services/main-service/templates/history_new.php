<?php

include 'config.php';
include 'auth.php';

$user_access = get_user_access($mysqli);
if (!$user_access['history']) {
    echo 'У вас нет доступа к этой странице';
    return;
}

echo '<link rel="stylesheet" type="text/css" href="public/main.css"/>';
$user_message = get_user_message($mysqli);
if ($user_message) {
    echo '<span class="message">'.$user_message.'</span><hr/>';
}

echo '<h1>История запросов</h1><hr/><a href="admin.php">Назад</a><br/><br/>';

if (!isset($_REQUEST['from'])) {
    $_REQUEST['from'] = /* date('01.m.Y'); / */ \date('d.m.Y');
}

$userid = get_user_id($mysqli);
$user_level = get_user_level($mysqli);
$user_area = get_user_area($mysqli);
$clientid = get_client_id($mysqli);

$conditions = '';
$join = '';
$order = $user_area >= 4 && isset($_REQUEST['order']) ? \mysqli_real_escape_string($mysqli, $_REQUEST['order']) : 'id DESC';
$limit = $user_area >= 4 && isset($_REQUEST['limit']) ? (int) ($_REQUEST['limit']) : 20;
$users = '';
$users_list = '';

echo '<form action="">';

$select = 'SELECT Id, Code FROM isphere.Client';
if ($user_area < 4) {
    $select .= " WHERE Id=$clientid";
    if ($user_area >= 3) {
        $select .= " OR MasterUserId=$userid";
    }
}
$select .= ' ORDER BY Code';
$sqlRes = $mysqli->query($select);
if ($user_area >= 3 && $sqlRes->num_rows > 0) {
    $clients = '<select name="client_id"><option value="">Все клиенты</option>';
    $clients .= '<option value="0"'.(isset($_REQUEST['client_id']) && '0' === $_REQUEST['client_id'] ? ' selected' : '').'>Без договора</option>';
    while ($result = $sqlRes->fetch_assoc()) {
        $clients .= '<option value="'.$result['Id'].'"'.(isset($_REQUEST['client_id']) && $result['Id'] == $_REQUEST['client_id'] ? ' selected' : '').'>'.$result['Code'].'</option>';
    }
    $clients .= '</select>';
}
$sqlRes->close();
if ($user_area >= 3) {
    echo $clients;
} else {
    $_REQUEST['client_id'] = $clientid;
}

$select = 'SELECT Id, Login, Locked FROM isphere.SystemUsers';
if ($user_area < 4) {
    $select .= " WHERE Id=$userid";
    if ($user_area >= 1) {
        $select .= " OR MasterUserId=$userid";
    }
    if ($user_area >= 2) {
        $select .= " OR ClientId=$clientid";
    }
    if ($user_area >= 3) {
        $select .= " OR ClientId IN (SELECT id FROM Client WHERE MasterUserId=$userid)";
    }
}
$select .= ' ORDER BY Login';
$sqlRes = $mysqli->query($select);
if ($sqlRes->num_rows > 1) {
    $users = ' <select name="user_id"><option value="">Все пользователи</option>';
    while ($result = $sqlRes->fetch_assoc()) {
        $users .= '<option value="'.$result['Id'].'"'.(isset($_REQUEST['user_id']) && $result['Id'] == $_REQUEST['user_id'] ? ' selected' : '').'>'.$result['Login'].($result['Locked'] ? ' (-)' : '').'</option>';
        $users_list .= ($users_list ? ',' : '').$result['Id'];
    }
    $users .= '</select>';
    if ($user_area < 4) {
        $conditions .= ' AND user_id IN ('.$users_list.')';
    }
} else {
    $_REQUEST['user_id'] = $userid;
}
$sqlRes->close();

//      if ($users || ($user_level<0)) {
echo $users;
if ($user_area >= 2) {
    echo ' <input type="checkbox" name="nested"'.(isset($_REQUEST['nested']) && $_REQUEST['nested'] ? ' checked="checked"' : '').'>+дочерние';
    if (isset($_REQUEST['limit']) && $limit) {
        echo ' <input type="hidden" name="limit" value="'.$limit.'">';
    }
}
//          if ($user_level<0) {
echo ' Период с <input type="text" name="from" value="'.(isset($_REQUEST['from']) ? $_REQUEST['from'] : '').'"> по <input type="text" name="to" value="'.(isset($_REQUEST['to']) ? $_REQUEST['to'] : '').'">';
//          }
if ($user_level < 0) {
    $select = 'SELECT DISTINCT source_name FROM isphere.ResponseNew ORDER BY 1';
    echo ' <select name="source"><option value="">Все источники</option>';
    $sqlRes = $mysqli->query($select);
    while ($result = $sqlRes->fetch_assoc()) {
        echo '<option value="'.$result['source_name'].'"'.(isset($_REQUEST['source']) && $result['source_name'] == $_REQUEST['source'] ? ' selected' : '').'>'.$result['source_name'].'</option>';
    }
    echo '</select>';
    $sqlRes->close();
}
if ($user_level < 0) {
    $select = 'SELECT DISTINCT checktype FROM isphere.ResponseNew ORDER BY 1';
    echo ' <select name="checktype"><option value="">Все проверки</option>';
    $sqlRes = $mysqli->query($select);
    while ($result = $sqlRes->fetch_assoc()) {
        if ($result['checktype']) {
            echo '<option value="'.$result['checktype'].'"'.(isset($_REQUEST['checktype']) && $result['checktype'] == $_REQUEST['checktype'] ? ' selected' : '').'>'.$result['checktype'].'</option>';
        }
    }
    echo '</select>';
    $sqlRes->close();
}
if ($user_level < 0) {
    echo '<select name="res_code">';
    echo '<option value=""'.(!isset($_REQUEST['res_code']) || !$_REQUEST['res_code'] ? ' selected' : '').'>Все результаты</option>';
    echo '<option value="200"'.(isset($_REQUEST['res_code']) && '200' == $_REQUEST['res_code'] ? ' selected' : '').'>Найден</option>';
    echo '<option value="204"'.(isset($_REQUEST['res_code']) && '204' == $_REQUEST['res_code'] ? ' selected' : '').'>Не найден</option>';
    echo '<option value="500"'.(isset($_REQUEST['res_code']) && '500' == $_REQUEST['res_code'] ? ' selected' : '').'>Ошибка</option>';
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
if (isset($_REQUEST['user_id']) && 0 != (int) $_REQUEST['user_id']) {
    $conditions .= ' AND (user_id='.(int) $_REQUEST['user_id'].(isset($_REQUEST['nested']) && $_REQUEST['nested'] ? ' OR user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId='.(int) $_REQUEST['user_id'].')' : '').')';
}
if (isset($_REQUEST['client_id']) && 0 != (int) $_REQUEST['client_id']) {
    $conditions .= ' AND client_id='.(int) $_REQUEST['client_id'];
}
if (isset($_REQUEST['client_id']) && '0' == $_REQUEST['client_id']) {
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
if (isset($_REQUEST['from']) && \strtotime($_REQUEST['from'])) {
    $conditions .= ' AND created_date >= str_to_date(\''.\date('Y-m-d', \strtotime($_REQUEST['from'])).'\', \'%Y-%m-%d\')';
    if (\date('H:i:s', \strtotime($_REQUEST['from'])) > '00:00:00') {
        $conditions .= ' AND created_at >= str_to_date(\''.\date('Y-m-d H:i:s', \strtotime($_REQUEST['from'])).'\', \'%Y-%m-%d %H:%i:%s\')';
    }
}
if (isset($_REQUEST['to']) && \strtotime($_REQUEST['to'])) {
    $conditions .= ' AND created_date <= str_to_date(\''.\date('Y-m-d', \strtotime($_REQUEST['to'])).'\', \'%Y-%m-%d\')';
    if (\date('H:i:s', \strtotime($_REQUEST['to'])) > '00:00:00') {
        $conditions .= ' AND created_at <= str_to_date(\''.\date('Y-m-d H:i:s', \strtotime($_REQUEST['to'])).'\', \'%Y-%m-%d %H:%i:%s\')';
    }
}
if (isset($_REQUEST['minid'])) {
    $conditions .= ' AND id < '.(int) $_REQUEST['minid'];
}
if (isset($_REQUEST['maxid'])) {
    $conditions .= ' AND id > '.(int) $_REQUEST['maxid'];
}
if (isset($_REQUEST['ip']) && $_REQUEST['ip']) {
    $conditions .= ' AND ip =\''.\mysqli_real_escape_string($mysqli, $_REQUEST['ip']).'\'';
}

$response_conditions = '';
if (isset($_REQUEST['source']) && $_REQUEST['source']) {
    $response_conditions .= ' AND res_code>0 AND source_name =\''.\mysqli_real_escape_string($mysqli, $_REQUEST['source']).'\'';
}
if (isset($_REQUEST['checktype']) && $_REQUEST['checktype']) {
    $response_conditions .= ' AND res_code>0 AND checktype =\''.\mysqli_real_escape_string($mysqli, $_REQUEST['checktype']).'\'';
}
if (isset($_REQUEST['res_code']) && (int) $_REQUEST['res_code']) {
    $response_conditions .= ' AND res_code='.(int) $_REQUEST['res_code'];
}
if ($response_conditions) {
    $conditions .= ' AND r.id IN (SELECT request_id id FROM ResponseNew WHERE 1=1 '.\strtr($conditions, ['AND id' => 'AND request_id'])." $response_conditions)";
}

//      $select = "SELECT r.*,u.login FROM RequestNew r, SystemUsers u $join WHERE r.user_id=u.Id $conditions ORDER BY $order LIMIT $limit";
$select = "SELECT r.*,(SELECT Login FROM SystemUsers WHERE id=r.user_id) login FROM RequestNew r WHERE 1=1 $conditions ORDER BY $order LIMIT $limit";
//      echo "$select<br/><br/>";
$sqlRes = $mysqli->query($select);
$minid = isset($_REQUEST['minid']) ? $_REQUEST['minid'] : 1000000000;
$maxid = isset($_REQUEST['maxid']) ? $_REQUEST['maxid'] : 0;
if (!$sqlRes) {
    echo "Ошибка при выполнении запроса\n";
} elseif ($sqlRes->num_rows) {
    echo "<table border=1>\n";
} else {
    echo "Запросов не найдено\n";
}
\file_put_contents('logs/history.csv', "id;request_id;crated_at;login;type;ip;sources;request_data;result_url\n");
while ($sqlRes && ($result = $sqlRes->fetch_assoc())) {
    if ($maxid < $result['id']) {
        $maxid = $result['id'];
    }
    if ($minid > $result['id']) {
        $minid = $result['id'];
    }
    //              print_r($result);
    echo "<tr>\n";
    $row = [];
    echo '<td>'.$result['id'].'</td>';
    $row[] = $result['id'];
    echo '<td>'.$result['external_id'].'</td>';
    $row[] = $result['external_id'];
    echo '<td>'.$result['created_at'].'</td>';
    $row[] = $result['created_at'];
    $delim = ' '; // '<br/>';
    $result['request'] = '';
    $numName = \str_pad($result['id'], 9, '0', \STR_PAD_LEFT);
    $titles = \str_split($numName, 3);
    if (\file_exists('/opt/xml/'.$titles[0].'/'.$titles[1].'/'.$titles[2].'_req.xml')) {
        $result['request'] = \file_get_contents('/opt/xml/'.$titles[0].'/'.$titles[1].'/'.$titles[2].'_req.xml');
    } elseif (\file_exists('/opt/xml/'.$titles[0].'/'.$titles[1].'.tar.gz')) {
        $result['request'] = \shell_exec('tar xzfO /opt/xml/'.$titles[0].'/'.$titles[1].'.tar.gz '.$titles[2].'_req.xml');
    }
    $result['request'] = \preg_replace("/<\?xml[^>]+>/", '', \substr($result['request'], \strpos($result['request'], '<')));
    $request = \simplexml_load_string($result['request']);
    echo '<td>'.$result['login']."</td>\n";
    $row[] = $result['login'];
    echo '<td>'.($result['type'] ?: 'api')."</td>\n";
    $row[] = $result['type'] ?: 'api';
    echo '<td>'.$result['ip']."</td>\n";
    $row[] = $result['ip'];
    echo '<td>'.(isset($request->sources) ? \strtr($request->sources, [' ' => '', "\u{a0}" => ''/* ','=>$delim */]) : (isset($request->PersonReq->sources) ? \strtr($request->PersonReq->sources, [/* ','=>$delim */]) : ''))."</td>\n";
    $row[] = $request->sources;
    echo '<td>';
    $data = '';
    if (!$request) {
        echo 'Данные запроса недоступны';
        $data = 'Данные запроса недоступны';
    }
    if (isset($request->PersonReq)) {
        $prequest = \json_decode(\json_encode($request->PersonReq), true);
        foreach ($prequest as $key => $val) {
            if ($val && !\is_array($val) && !\in_array($key, ['UserID', 'Password', 'requestId', 'sources'])) {
                echo /* $key.": ". */ $val.$delim;
                $data .= $val.$delim;
            }
        }
    }
    if (isset($request->PhoneReq)) {
        foreach ($request->PhoneReq as $req) {
            echo $req->phone.$delim;
            $data .= $req->phone.$delim;
        }
    }
    if (isset($request->EmailReq)) {
        foreach ($request->EmailReq as $req) {
            echo $req->email.$delim;
            $data .= $req->email.$delim;
        }
    }
    if (isset($request->SkypeReq)) {
        foreach ($request->SkypeReq as $req) {
            echo $req->skype.$delim;
            $data .= $req->skype.$delim;
        }
    }
    if (isset($request->NickReq)) {
        foreach ($request->NickReq as $req) {
            echo $req->nick.'<br />';
            $data .= $req->nick.$delim;
        }
    }
    if (isset($request->URLReq)) {
        foreach ($request->URLReq as $req) {
            echo $req->url.$delim;
            $data .= $req->url.$delim;
        }
    }
    if (isset($request->CarReq)) {
        $prequest = \json_decode(\json_encode($request->CarReq), true);
        foreach ($prequest as $key => $val) {
            if ($val && !\is_array($val)) {
                echo /* $key.": ". */ $val.$delim;
                $data .= $val.$delim;
            }
        }
    }
    if (isset($request->IPReq)) {
        foreach ($request->IPReq as $req) {
            echo $req->ip.$delim;
            $data .= $req->ip.$delim;
        }
    }
    if (isset($request->OrgReq)) {
        $prequest = \json_decode(\json_encode($request->OrgReq), true);
        foreach ($prequest as $key => $val) {
            if ($val && !\is_array($val)) {
                echo /* $key.": ". */ $val.$delim;
                $data .= $val.$delim;
            }
        }
    }
    if (isset($request->OtherReq)) {
        $prequest = \json_decode(\json_encode($request->OtherReq), true);
        foreach ($prequest as $key => $val) {
            if ($val && !\is_array($val)) {
                echo /* $key.": ". */ $val.$delim;
                $data .= $val.$delim;
            }
        }
    }
    if (isset($request->CardReq)) {
        foreach ($request->CardReq as $req) {
            echo $req->card.$delim;
            $data .= $req->card.$delim;
        }
    }
    echo '</td>';
    $row[] = '"'.\trim($data).'"';
    //		$response = simplexml_load_string($result['response']);
    echo '<td>';
    if ($request) {
        echo '<a href="showresult.php?id='.$result['id'].'" target=_blank>Просмотр</a>'.$delim.'<a href="showresult.php?id='.$result['id'].'&mode=pdf" target=_blank>PDF</a>&nbsp;<a href="showresult.php?id='.$result['id'].'&mode=xml" target=_blank>XML</a>';
        global $serviceurl;
        $row[] = $serviceurl.'showresult.php?id='.$result['id'];
    } else {
        echo 'Результаты обработки отсутствуют';
        $row[] = 'Результаты обработки отсутствуют';
    }
    echo '</td>';
    echo "</tr>\n";
    \file_put_contents('logs/history.csv', \implode(';', $row)."\n", \FILE_APPEND);
}
if ($sqlRes && $sqlRes->num_rows) {
    echo "</table>\n";
}
echo "<br />\n";
$querystr = \preg_replace("/\&m(in|ax)id=\d+/", '', \getenv('QUERY_STRING'));
echo '<a href="history_new.php?'.$querystr.'&maxid='.($maxid ?: '').'"> << </a> ';
if ($sqlRes && ($sqlRes->num_rows == $limit)) {
    echo '<a href="history_new.php?'.$querystr.'&minid='.$minid.'"> >> </a>';
}
if ($sqlRes) {
    $sqlRes->close();
}
$mysqli->close();

include 'footer.php';
