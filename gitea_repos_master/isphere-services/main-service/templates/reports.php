<?php

/**
 * @global Connection $connection
 * @global SystemUser $user
 */

use App\Entity\SystemUser;
use Doctrine\DBAL\Connection;

$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';

$userid = $user->getId();
$user_level = $user->getAccessLevel();
$user_area = $user->getResultsArea() ?: $user->getAccessArea();
$clientid = $user->getClient()?->getId();
$conditions = '';
$rep_conditions = '';
$users = '';
$users_list = '';
?>
<form action="" class="d-none">
    <div class="row align-items-center">
        <?php

        $select = 'SELECT Id, Code FROM Client ORDER BY Code';
        $sqlRes = $connection->executeQuery($select);
        if ($sqlRes->rowCount() > 1) {
            $clients = '<div class="col-auto">';
            $clients .= '<select class="form-select form-select-sm" name="client_id"><option value="">Все клиенты</option>';
            $clients .= '<option value="0"' . (isset($_REQUEST['client_id']) && '0' === $_REQUEST['client_id'] ? ' selected' : '') . '>Без договора</option>';
            while ($result = $sqlRes->fetchAssociative()) {
                $clients .= '<option value="' . $result['Id'] . '"' . (isset($_REQUEST['client_id']) && $result['Id'] == $_REQUEST['client_id'] ? ' selected' : '') . '>' . $result['Code'] . '</option>';
            }
            $clients .= '</select>';
            $clients .= '</div>';
        }
        if ($user_area > 2) {
            echo $clients;
        } else {
            $_REQUEST['client_id'] = $clientid;
        }

        $select = 'SELECT Id, Login, Locked FROM SystemUsers';
        if ($user_area <= 2) {
            $select .= " WHERE Id=$userid";
            if ($user_area >= 1) {
                $select .= "  OR MasterUserId=$userid";
                if ($user_area > 1) {
                    $select .= " OR MasterUserId IN (SELECT id FROM SystemUsers WHERE MasterUserId=$userid)";
                }
            } else {
                $_REQUEST['user_id'] = $userid;
            }
        }
        $select .= ' ORDER BY Login';
        $sqlRes = $connection->executeQuery($select);
        if ($sqlRes->rowCount() > 1) {
            $users = '<div class="col-auto">';
            $users .= '<select class="form-select form-select-sm" name="user_id"><option value="">Все пользователи</option>';
            while ($result = $sqlRes->fetchAssociative()) {
                $users .= '<option value="' . $result['Id'] . '"' . (isset($_REQUEST['user_id']) && $result['Id'] == $_REQUEST['user_id'] ? ' selected' : '') . '>' . $result['Login'] . ($result['Locked'] ? ' (-)' : '') . '</option>';
                $users_list .= ($users_list ? ',' : '') . $result['Id'];
            }
            $users .= '</select>';
            $users .= '</div>';
            if ($user_area <= 2) {
                $conditions .= ' AND user_id IN (' . $users_list . ')';
            }
        } else {
            $_REQUEST['user_id'] = $userid;
        }
        echo $users;
        if ($user_area >= 2) {
            ?>
            <div class="col-auto">
                <div class="form-check">
                    <input id="nested" class="form-check-input" type="checkbox"
                           name="nested"<?= $_REQUEST['nested'] ?? false ? ' checked="checked"' : '' ?>/>
                    <label for="nested" class="form-check-label">+дочерние</label>
                </div>
            </div>
            <?php
        }

        ?>
        <div class="col-auto">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="col-form-label" for="from">Период с</label>
                </div>
                <div class="col-auto">
                    <input size="4" class="form-control form-control-sm" id="from" name="from" value="<?= (isset($_REQUEST['from']) ? $_REQUEST['from'] : date('d.m.Y')) ?>"/>
                </div>
                <div class="col-auto">
                    <label class="col-form-label" for="to">по</label>
                </div>
                <div class="col-auto">
                    <input size="4" class="form-control form-control-sm" id="to" name="to" value="<?= (isset($_REQUEST['to']) ? $_REQUEST['to'] : date('d.m.Y')) ?>"/>
                </div>
            </div>
        </div>

        <?php
        if ($user_level < 0) {
            ?>
            <div class="col-auto row align-items-center">
                <div class="col-auto" style="margin-right: -2ex;">IP</div>
                <div class="col-auto">
                    <input class="form-control form-control-sm" size="10" type="text" name="ip" value="<?= (isset($_REQUEST['ip']) ? $_REQUEST['ip'] : '') ?>">
                </div>
            </div>
            <?php
        }
        echo '</div>';
        echo '<div class="row align-items-center">';
        //      if ($user_level<0) {
        $select = 'SELECT DISTINCT source_name FROM ResponseNew ORDER BY 1';
        echo '<div class="col-auto">';
        echo ' <select class="form-select form-select-sm" name="source"><option value="">Все источники</option>';
        $sqlRes = $connection->executeQuery($select);
        while ($result = $sqlRes->fetchAssociative()) {
            echo '<option value="' . $result['source_name'] . '"' . (isset($_REQUEST['source']) && $result['source_name'] == $_REQUEST['source'] ? ' selected' : '') . '>' . $result['source_name'] . '</option>';
        }
        echo '</select>';
        echo '</div>';
        //      }
        //      if ($user_level<0) {
        $select = 'SELECT DISTINCT checktype FROM ResponseNew ORDER BY 1';
        echo '<div class="col-auto">';
        echo ' <select class="form-select form-select-sm" name="checktype"><option value="">Все проверки</option>';
        $sqlRes = $connection->executeQuery($select);
        while ($result = $sqlRes->fetchAssociative()) {
            if ($result['checktype']) {
                echo '<option value="' . $result['checktype'] . '"' . (isset($_REQUEST['checktype']) && $result['checktype'] == $_REQUEST['checktype'] ? ' selected' : '') . '>' . $result['checktype'] . '</option>';
            }
        }
        echo '</select>';
        echo '</div>';
        //      }
        echo '<div class="col-auto">';
        echo ' <select class="form-select form-select-sm" name="type">';
        echo '<option value="dates"' . ('dates' == $type ? ' selected' : '') . '>По датам</option>';
        echo '<option value="months"' . ('months' == $type ? ' selected' : '') . '>По месяцам</option>';
        echo '<option value="hours"' . ('hours' == $type ? ' selected' : '') . '>По часам</option>';
        echo '<option value="minutes"' . ('minutes' == $type ? ' selected' : '') . '>По минутам</option>';
        echo '<option value="sources"' . ('sources' == $type || '' == $type ? ' selected' : '') . '>По источникам</option>';
        echo '<option value="checktypes"' . ('checktypes' == $type ? ' selected' : '') . '>По проверкам</option>';
        if ($user_area >= 1) {
            echo '<option value="users"' . ('users' == $type ? ' selected' : '') . '>По пользователям</option>';
        }
        if ($user_area > 2) {
            echo '<option value="clients"' . ('clients' == $type ? ' selected' : '') . '>По клиентам</option>';
        }
        echo '<option value="ips"' . ('ips' == $type ? ' selected' : '') . '>По IP-адресам</option>';
        echo '</select>';
        echo '</div>';
        /*
              if ($user_level<0) {
                  echo ' Поиск <input type="text" name="find" value="'.(isset($_REQUEST['find'])?$_REQUEST['find']:'').'">';
                  if(isset($_REQUEST['find']) && strlen($_REQUEST['find'])){
                      $conditions .= " AND locate('".mysqli_real_escape_string($mysqli,$_REQUEST['find'])."',response)>0";
                  }
              }
        */
        if ($user_area >= 3) {
            echo '<div class="col-auto">';
            echo ' <select class="form-select form-select-sm" name="pay">';
            echo '<option value="all"' . (!isset($_REQUEST['pay']) || 'all' == $_REQUEST['pay'] ? ' selected' : '') . '>Не тарифицировать</option>';
            echo '<option value="separate"' . (isset($_REQUEST['pay']) && 'separate' == $_REQUEST['pay'] ? ' selected' : '') . '>Все тарифы</option>';
            echo '<option value="pay"' . (isset($_REQUEST['pay']) && 'pay' == $_REQUEST['pay'] ? ' selected' : '') . '>Платные</option>';
            echo '<option value="free"' . (isset($_REQUEST['pay']) && 'free' == $_REQUEST['pay'] ? ' selected' : '') . '>Бесплатные</option>';
            echo '<option value="test"' . (isset($_REQUEST['pay']) && 'test' == $_REQUEST['pay'] ? ' selected' : '') . '>Тестовые</option>';
            echo '</select>';
            echo '</div>';
        }
        echo '<div class="col-auto">';
        echo ' <select class="form-select form-select-sm" name="order">';
        echo '<option value="1"' . (!isset($_REQUEST['order']) || '1' == $_REQUEST['order'] ? ' selected' : '') . '>По умолчанию</option>';
        if ($user_area >= 3) {
            echo '<option value="total desc"' . (isset($_REQUEST['order']) && 'total desc' == $_REQUEST['order'] ? ' selected' : '') . '>По убыванию суммы</option>';
        }
        echo '<option value="reqcount desc"' . (isset($_REQUEST['order']) && 'reqcount desc' == $_REQUEST['order'] ? ' selected' : '') . '>По убыванию обращений</option>';
        echo '<option value="rescount desc"' . (isset($_REQUEST['order']) && 'rescount desc' == $_REQUEST['order'] ? ' selected' : '') . '>По убыванию запросов</option>';
        echo '</select>';
        echo '</div>';
        echo '<div class="col-auto">';
        echo ' <input class="btn btn-sm btn-primary" type="submit" value="Обновить">';
        echo '</div>';
        ?>
    </div>
</form>

<?php

$u = isset($_REQUEST['nested']) && $_REQUEST['nested'] && $user_area > 1 && isset($_REQUEST['user_id']) && 0 == (int)$_REQUEST['user_id'] ? 'm' : 'u';
$fields = [
    'dates' => 'r.created_date',
    'months' => "date_format(r.created_date,'%Y-%m') as month",
    'hours' => 'r.hour',
    'minutes' => 'r.minute',
    'sources' => 'r.source_name',
    'users' => "$u.login",
    'clients' => 'c.code',
    'checktypes' => 'r.checktype',
    'ips' => 'req.ip',
];

if (!$type) {
} elseif (!isset($fields[$type])) {
    echo 'Неизвестный тип отчета';
} elseif (!isset($_REQUEST['from']) || !\strtotime($_REQUEST['from'])) {
    echo 'Укажите начальную дату';
} elseif (!isset($_REQUEST['to']) || !\strtotime($_REQUEST['to'])) {
    echo 'Укажите конечную дату';
} elseif (/* $user_level>=0 && */
    \in_array($_REQUEST['client_id'], ['', '15', '19', '25']) && \date('d.m.Y', \strtotime($_REQUEST['from'])) != \date('d.m.Y', \strtotime($_REQUEST['to']))) {
    echo 'Получение статистики временно ограничено только одной датой. Приносим извинения за неудобства.<br/>Если вам нужна статистика за период, напишите нам в онлайн-чат или на e-mail <a href="mailto:support@i-sphere.ru">support@i-sphere.ru</a>';
} else {
    $field = $fields[$type];

    if (isset($_REQUEST['client_id']) && 0 != (int)$_REQUEST['client_id']) {
        $conditions .= ' AND r.client_id=' . (int)$_REQUEST['client_id'];
    }
    if (isset($_REQUEST['client_id']) && '0' == $_REQUEST['client_id']) {
        $conditions .= ' AND r.client_id is null';
    }
    if (isset($_REQUEST['user_id']) && 0 != (int)$_REQUEST['user_id']) {
        $conditions .= ' AND (r.user_id=' . (int)$_REQUEST['user_id'];
        if (($user_area >= 1) && isset($_REQUEST['nested']) && $_REQUEST['nested']) {
            $conditions .= ' OR r.user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId=' . (int)$_REQUEST['user_id'] . ')';
            if ($user_area > 1) {
                $conditions .= ' OR r.user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId IN (SELECT id FROM SystemUsers WHERE MasterUserId=' . (int)$_REQUEST['user_id'] . '))';
            }
        }
        $conditions .= ')';
    }
    if (isset($_REQUEST['from']) && \strtotime($_REQUEST['from'])) {
        //          $conditions .= ' AND created_date >= str_to_date(\''.date('Y-m-d',strtotime($_REQUEST['from'])).'\', \'%Y-%m-%d\')';
        $conditions .= ' AND r.created_date >= \'' . \date('Y-m-d', \strtotime($_REQUEST['from'])) . '\'';
        if (\date('H:i:s', \strtotime($_REQUEST['from'])) > '00:00:00') {
            //              $conditions .= ' AND created_at >= str_to_date(\''.date('Y-m-d H:i:s',strtotime($_REQUEST['from'])).'\', \'%Y-%m-%d %H:%i:%s\')';
            $conditions .= ' AND r.created_at >= \'' . \date('Y-m-d H:i:s', \strtotime($_REQUEST['from'])) . '\'';
        }
    }
    if (isset($_REQUEST['to']) && \strtotime($_REQUEST['to'])) {
        //          $conditions .= ' AND created_date <= str_to_date(\''.date('Y-m-d',strtotime($_REQUEST['to'])).'\', \'%Y-%m-%d\')';
        $conditions .= ' AND r.created_date <= \'' . \date('Y-m-d', \strtotime($_REQUEST['to'])) . '\'';
        if (\date('H:i:s', \strtotime($_REQUEST['to'])) > '00:00:00') {
            //              $conditions .= ' AND created_at <= str_to_date(\''.date('Y-m-d H:i:s',strtotime($_REQUEST['to'])).'\', \'%Y-%m-%d %H:%i:%s\')';
            $conditions .= ' AND r.created_at <= \'' . \date('Y-m-d H:i:s', \strtotime($_REQUEST['to'])) . '\'';
        }
    }
    if (isset($_REQUEST['source']) && $_REQUEST['source']) {
        $conditions .= ' AND r.source_name=' . $connection->createQueryBuilder()->expr()->literal($_REQUEST['source']);
    }
    if (isset($_REQUEST['checktype']) && $_REQUEST['checktype']) {
        $conditions .= ' AND r.checktype=' . $connection->createQueryBuilder()->expr()->literal($_REQUEST['checktype']);
    }
    if ($user_level < 0 && isset($_REQUEST['ip']) && $_REQUEST['ip']) {
        $rep_conditions .= ' AND req.ip=' . $connection->createQueryBuilder()->expr()->literal($_REQUEST['ip']);
    }
    if (isset($_REQUEST['pay'])) {
        if ('free' == $_REQUEST['pay']) {
            $rep_conditions .= ' AND IFNULL(u.DefaultPrice,m.DefaultPrice)=0';
        }
        if ('pay' == $_REQUEST['pay']) {
            $rep_conditions .= ' AND IFNULL(u.DefaultPrice,m.DefaultPrice)>0';
        }
        if ('test' == $_REQUEST['pay']) {
            $rep_conditions .= ' AND IFNULL(u.DefaultPrice,m.DefaultPrice) IS NULL';
        }
    }

    $addfields = ('dates' == $type || 'months' == $type || \strpos(
            $conditions,
            'created_date'
        ) ? 'created_date,' : '') ./* (strpos($conditions,'created_at')?'created_at,':''). */ /* ($type=='sources'?'source_name,':'').($type=='clients'?'client_id,':'').($type=='users'?'user_id,':''). */
        ('checktypes' == $type || \strpos($conditions, 'checktype') ? 'checktype,' : '');
    $addgroups = $addfields;
    $addfields .= ('hours' == $type ? 'hour(created_at) as hour,' : '');
    $addgroups .= ('hours' == $type ? 'hour,' : '');
    $addfields .= ('minutes' == $type ? 'minute(created_at) as minute,' : '');
    $addgroups .= ('minutes' == $type ? 'minute,' : '');

    $viewtype = $type;
    if (isset($_REQUEST['checktype']) && $_REQUEST['checktype']) {
        $viewtype = 'checktypes';
    }
    if ('checktypes' != $type && isset($_REQUEST['source']) && $_REQUEST['source']) {
        $viewtype = 'sources';
    }

    //      $tmpview = ($viewtype=='checktypes') ? 'REPORT_'.$userid.'_'.time() : false;
    $tmpview = 'REPORT_' . $userid . '_' . \time();
    //      $table = $tmpview ? $tmpview : (strpos('created_at',$addfiels.$conditions) || !isset($_REQUEST['to']) || !strtotime($_REQUEST['to']) ? 'RequestSource':'RequestDate');
    $table = $tmpview ?: 'RequestSource';

    if ($tmpview) {
        // if ($viewtype=='clients' OR (isset($_REQUEST['client_id']) && $_REQUEST['client_id']!="") OR (isset($_REQUEST['from']) && date('Y-m-d',strtotime($_REQUEST['from']))>='2020-06-01')) {
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
        // CREATE VIEW $tmpview AS
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

    if ('users' == $type) {
        $field2 .= "$u.id user_id,";
        $group2 .= ",$u.id";
    }
    if ('clients' == $type) {
        $field2 .= 'c.name,c.id client_id,';
        $group2 .= ',c.name,c.id';
    }
    if ('ips' == $type || \strpos($rep_conditions, 'req.ip')) {
        $group2 .= ',req.ip';
        $join2 .= 'JOIN RequestNew req ON r.request_id=req.id';
    }

    if ((isset($_REQUEST['pay']) && ('separate' == $_REQUEST['pay'] || 'pay' == $_REQUEST['pay'])) || (isset($_REQUEST['order']) && false !== \strpos(
                $_REQUEST['order'],
                'total'
            ))) {
        if ('separate' == $_REQUEST['pay']) {
            $payfields .= <<<SQL
, SUM(r.success_count>0 AND (COALESCE(u.DefaultPrice,m.DefaultPrice)=0 OR source_name IN (SELECT source_name FROM UserSourcePrice WHERE (user_id=u.id OR user_id=m.id) AND price=0))) nonpay
, SUM(r.success_count>0 AND (COALESCE(u.DefaultPrice,m.DefaultPrice) IS NULL)) test
, SUM(r.success_count>0 AND (COALESCE(u.DefaultPrice,m.DefaultPrice,0)<>0 AND source_name NOT IN (SELECT source_name FROM UserSourcePrice WHERE (user_id=u.id OR user_id=m.id) AND price=0))) pay
SQL;
        }
        $payfields .= <<<SQL
, SUM(CASE WHEN r.success_count>0 THEN IFNULL((SELECT MIN(price) FROM UserSourcePrice WHERE (user_id=u.id OR user_id=m.id) AND source_name=r.source_name),IFNULL(u.DefaultPrice,m.DefaultPrice)) ELSE 0 END) total
SQL;
        $field2 .= 'users' == $type || 'clients' == $type || $user_area <= 1 || (isset($_REQUEST['user_id']) && $_REQUEST['user_id']) || (isset($_REQUEST['client_id']) && '' != $_REQUEST['client_id']) ? 'IFNULL((SELECT MIN(price) FROM UserSourcePrice WHERE (user_id=u.id OR user_id=m.id) AND source_name=r.source_name),IFNULL(u.DefaultPrice,m.DefaultPrice)) price,' : '';
        $group2 .= '' != $field2 ? ',price' : '';
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

    if (isset($_REQUEST['order'])) {
        $sql .= ' ORDER BY ' . $_REQUEST['order'];
    }

    $title = [
        'created_date' => 'Дата',
        'month' => 'Месяц',
        'hour' => 'Час',
        'minute' => 'Минута',
        'source_name' => 'Источник',
        'checktype' => 'Проверка',
        'login' => 'Логин',
        'code' => 'Код',
        'name' => 'Наименование',
        'ip' => 'IP',
        'reqcount' => 'Обращений<br/>к сервису',
        'rescount' => 'Запросов<br/>в источник',
        'success' => 'Успешно<br/>обработано',
        'error' => 'Ошибок',
        'nonpay' => 'Бесплатные<br/>запросы',
        'test' => 'Тестовые<br/>запросы',
        'pay' => 'Платные<br/>запросы',
        'price' => 'Цена',
        'total' => 'Сумма',
        'hit' => 'Найдены<br/>данные',
        'process' => 'Среднее<br/>время, с',
        'successrate' => 'Успешно, %',
        'hitrate' => 'Найдено, %',
    ];
    $hide = [
        'dates' => [
            'hit' => 1,
            'process' => 1,
        ],
        'months' => [
            'hit' => 1,
            'process' => 1,
        ],
        'hours' => [
            'hit' => 1,
            'process' => 1,
        ],
        'minutes' => [
            'hit' => 1,
            'process' => 1,
        ],
        'sources' => [
            'reqcount' => 1,
        ],
        'checktypes' => [
            'reqcount' => 1,
            'pay' => 1,
            'nonpay' => 1,
            'test' => 1,
            'price' => 1,
            'total' => 1,
        ],
        'users' => [
            'hit' => 1,
            'process' => 1,
            'user_id' => 1,
        ],
        'clients' => [
            'hit' => 1,
            'process' => 1,
        ],
        'ips' => [
            'hit' => 1,
            'process' => 1,
        ],
    ];
    $total = [
        'reqcount' => 0,
        'rescount' => 0,
        'success' => 0,
        'error' => 0,
        'pay' => 0,
        'nonpay' => 0,
        'test' => 0,
        'hit' => 0,
        'total' => 0,
    ];

    $i = 0;
    $sqlRes = $connection->executeQuery($sql);
    echo "<table class='table'>\n";
    while ($result = $sqlRes->fetchAssociative()) {
        //              print_r($result);
        if (0 == $i) {
            $first = $result;
            echo "<tr>\n";
            foreach ($result as $key => $val) {
                if (!isset($hide[$viewtype][$key]) && isset($title[$key])) {
                    echo '<th>' . $title[$key] . '</th>';
                }
            }
            if ('sources' == $viewtype || 'checktypes' == $viewtype) {
                echo '<th>' . $title['successrate'] . '</th>';
                echo '<th>' . $title['hitrate'] . '</th>';
            }
            echo "</tr>\n";
        }
        echo "<tr>\n";
        foreach ($result as $key => $val) {
            if (!isset($hide[$viewtype][$key]) && isset($title[$key])) {
                if (isset($total[$key])) {
                    $total[$key] += $val;
                }
                echo '<td ' . (\is_numeric($val) ? 'class="right"' : '') . '>';
                $params = false;
                if ('created_date' == $key) {
                    $params = $_REQUEST;
                    $params['from'] = $val;
                    $params['to'] = $val;
                    $params['type'] = (!isset($_REQUEST['source']) || !$_REQUEST['source']) && (!isset($_REQUEST['checktype']) || !$_REQUEST['checktype']) ? 'sources' : (!isset($_REQUEST['user_id']) || !$_REQUEST['user_id'] ? 'users' : 'hours');
                }
                if ('hour' == $key && isset($_REQUEST['from']) && \strtotime($_REQUEST['from']) && ((isset($_REQUEST['to']) && \strtotime($_REQUEST['to']) && \date(
                                'd.m.Y',
                                \strtotime($_REQUEST['from'])
                            ) == \date('d.m.Y', \strtotime($_REQUEST['to']))) || ((!isset($_REQUEST['to']) || !$_REQUEST['to']) && \date(
                                'd.m.Y',
                                \strtotime($_REQUEST['from'])
                            ) == \date('d.m.Y')))) {
                    $params = $_REQUEST;
                    $params['from'] = \date('d.m.Y', \strtotime($_REQUEST['from'])) . ' ' . $val . ':00:00';
                    $params['to'] = \date('d.m.Y', \strtotime($_REQUEST['from'])) . ' ' . $val . ':59:59';
                    $params['type'] = (!isset($_REQUEST['source']) || !$_REQUEST['source']) && (!isset($_REQUEST['checktype']) || !$_REQUEST['checktype']) ? 'sources' : (!isset($_REQUEST['user_id']) || !$_REQUEST['user_id'] ? 'users' : 'minutes');
                }
                if ('minute' == $key && isset($_REQUEST['from']) && \strtotime($_REQUEST['from']) && ((isset($_REQUEST['to']) && \strtotime($_REQUEST['to']) && \date(
                                'd.m.Y H',
                                \strtotime($_REQUEST['from'])
                            ) == \date('d.m.Y H', \strtotime($_REQUEST['to']))) || ((!isset($_REQUEST['to']) || !$_REQUEST['to']) && \date(
                                'd.m.Y H',
                                \strtotime($_REQUEST['from'])
                            ) == \date(
                                'd.m.Y H'
                            )))) {
                    $params = $_REQUEST;
                    $params['from'] = \date('d.m.Y H', \strtotime($_REQUEST['from'])) . ':' . $val . ':00';
                    $params['to'] = \date('d.m.Y H', \strtotime($_REQUEST['from'])) . ':' . $val . ':59';
                    $params['type'] = (!isset($_REQUEST['source']) || !$_REQUEST['source']) && (!isset($_REQUEST['checktype']) || !$_REQUEST['checktype']) ? 'sources' : (!isset($_REQUEST['user_id']) || !$_REQUEST['user_id'] ? 'users' : 'minutes');
                }
                if ('source_name' == $key || 'checktype' == $key) {
                    $params = $_REQUEST;
                    $params['source_name' == $key ? 'source' : 'checktype'] = $val;
                    $params['type'] = !isset($_REQUEST['from']) || !\strtotime($_REQUEST['from']) || !isset($_REQUEST['to']) || !\strtotime($_REQUEST['to']) || \date(
                        'd.m.Y',
                        \strtotime($_REQUEST['from'])
                    ) != \date('d.m.Y', \strtotime($_REQUEST['to'])) ? 'dates' : (!isset($_REQUEST['user_id']) || !$_REQUEST['user_id'] ? 'users' : 'hours');
                }
                if ('code' == $key) {
                    $params = $_REQUEST;
                    $params['client_id'] = $result['client_id'];
                    $params['type'] = (!isset($_REQUEST['source']) || !$_REQUEST['source']) && (!isset($_REQUEST['checktype']) || !$_REQUEST['checktype']) ? 'sources' : (!isset($_REQUEST['from']) || !\strtotime(
                        $_REQUEST['from']
                    ) || !isset($_REQUEST['to']) || !\strtotime($_REQUEST['to']) || \date('d.m.Y', \strtotime($_REQUEST['from'])) != \date(
                        'd.m.Y',
                        \strtotime($_REQUEST['to'])
                    ) ? 'dates' : (!isset($_REQUEST['user_id']) || !$_REQUEST['user_id'] ? 'users' : 'hours'));
                }
                if ('login' == $key) {
                    $params = $_REQUEST;
                    $params['user_id'] = $result['user_id'];
                    $params['type'] = (!isset($_REQUEST['source']) || !$_REQUEST['source']) && (!isset($_REQUEST['checktype']) || !$_REQUEST['checktype']) ? 'sources' : (!isset($_REQUEST['from']) || !\strtotime(
                        $_REQUEST['from']
                    ) || !isset($_REQUEST['to']) || !\strtotime($_REQUEST['to']) || \date('d.m.Y', \strtotime($_REQUEST['from'])) != \date(
                        'd.m.Y',
                        \strtotime($_REQUEST['to'])
                    ) ? 'dates' : 'hours');
                }
                if ($params && ('hours' == $params['type'] || 'minutes' == $params['type']) && isset($params['from']) && \strtotime(
                        $params['from']
                    ) && ((isset($params['to']) && \strtotime(
                                $params['to']
                            ) && \date('d.m.Y H', \strtotime($params['from'])) == \date('d.m.Y H', \strtotime($params['to']) - 1)) || (!isset($params['to']) && \date(
                                'd.m.Y H',
                                \strtotime($params['from'])
                            ) == \date('d.m.Y H')))) {
                    unset($params['type']);
                    unset($params['pay']);
                    unset($params['order']);
                    echo '<a href="/history?' . \http_build_query($params) . '">';
                } elseif ($params) {
                    echo '<a href="/reports?' . \http_build_query($params) . '">';
                }
                if ('process' == $key) {
                    $val = \number_format($val, 1, ',', '');
                }
                if (('price' == $key || 'total' == $key) && \strlen($val)) {
                    $val = \number_format($val, 2, ',', '');
                }
                echo $val;
                if ($params) {
                    echo '</a>';
                }
                echo '</td>';
            }
        }
        if ('sources' == $viewtype || 'checktypes' == $viewtype) {
            echo '<td class="right">' . ($result['rescount'] > 10 ? \number_format($result['success'] / $result['rescount'] * 100, 2, ',', '') : '') . '</td>';
            echo '<td class="right">' . ($result['success'] > 10 ? \number_format($result['hit'] / $result['success'] * 100, 2, ',', '') : '') . '</td>';
        }
        echo "</tr>\n";
        ++$i;
    }
    if (0 == $i) {
        echo 'Нет данных';
    } else {
        foreach ($first as $key => $val) {
            if (!isset($hide[$viewtype][$key]) && isset($title[$key])) {
                echo '<td class="right"><b>' . (isset($total[$key]) ? $total[$key] : '') . '</b></td>';
            }
        }
        if ((isset($_REQUEST['source']) && $_REQUEST['source']) || (isset($_REQUEST['checktype']) && $_REQUEST['checktype'])) {
            echo '<td class="right"><b>' . ($total['rescount'] > 10 ? \number_format($total['success'] / $total['rescount'] * 100, 2, ',', '') : '') . '</b></td>';
            echo '<td class="right"><b>' . ($total['success'] > 10 ? \number_format($total['hit'] / $total['success'] * 100, 2, ',', '') : '') . '</b></td>';
        } elseif ('sources' == $viewtype) {
            echo '<td class="right"></td>';
            echo '<td class="right"></td>';
        }
    }
    //      if ($tmpview) $mysqli->query("DROP VIEW $tmpview");
    echo "</table>\n";
}
