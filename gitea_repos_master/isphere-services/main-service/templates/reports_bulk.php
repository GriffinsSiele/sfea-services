<?php

use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require 'config.php';
require 'bulkAuto/vendor/autoload.php';

$report_path = $logpath.'reports/';

$user_level = -1;
$userid = 5;
$user_area = 0;
//      include ('auth.php');

\set_time_limit(10800);

function report($type, $name = false): void
{
    global $mysqli, $userid;
    global $user_level;
    global $user_area;
    global $report_path;

    $fields = [
        'dates' => 'r.created_date',
        'months' => "date_format(r.created_date,'%Y-%m') as month",
        'hours' => 'r.hour',
        'sources' => 'r.source_name',
        'params' => "substring(start_param,1,locate('[',concat(start_param,'['))-1) as start_param",
        'users' => 'u.login',
        'clients' => 'c.code',
        'checktypes' => 'r.checktype',
        'ips' => 'r.ip',
    ];

    $conditions = '';
    $rep_conditions = '';

    $field = $fields[$type];

    if (isset($_REQUEST['client_id']) && 0 != (int) $_REQUEST['client_id']) {
        $conditions .= ' AND client_id='.(int) $_REQUEST['client_id'];
    }
    if (isset($_REQUEST['client_id']) && '0' == $_REQUEST['client_id']) {
        $conditions .= ' AND client_id is null';
    }
    if (isset($_REQUEST['user_id']) && \preg_match("/[,\d]+/", $_REQUEST['user_id'])) {
        $conditions .= ' AND (user_id IN ('.$_REQUEST['user_id'].')';
        if (($user_area >= 1) && isset($_REQUEST['nested']) && $_REQUEST['nested']) {
            $conditions .= ' OR user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId IN ('.$_REQUEST['user_id'].'))';
            if ($user_area > 1) {
                $conditions .= ' OR user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId IN (SELECT id FROM SystemUsers WHERE MasterUserId IN ('.$_REQUEST['user_id'].')))';
            }
        }
        $conditions .= ')';
    }
    if (isset($_REQUEST['from']) && \strtotime($_REQUEST['from'])) {
        //          $conditions .= ' AND created_date >= str_to_date(\''.date('Y-m-d',strtotime($_REQUEST['from'])).'\', \'%Y-%m-%d\')';
        $conditions .= ' AND created_date >= \''.\date('Y-m-d', \strtotime($_REQUEST['from'])).'\'';
        if (\date('H:i:s', \strtotime($_REQUEST['from'])) > '00:00:00') {
            //              $conditions .= ' AND created_at >= str_to_date(\''.date('Y-m-d H:i:s',strtotime($_REQUEST['from'])).'\', \'%Y-%m-%d %H:%i:%s\')';
            $conditions .= ' AND created_at >= \''.\date('Y-m-d H:i:s', \strtotime($_REQUEST['from'])).'\'';
        }
    }
    if (isset($_REQUEST['to']) && \strtotime($_REQUEST['to'])) {
        //          $conditions .= ' AND created_date <= str_to_date(\''.date('Y-m-d',strtotime($_REQUEST['to'])).'\', \'%Y-%m-%d\')';
        $conditions .= ' AND created_date <= \''.\date('Y-m-d', \strtotime($_REQUEST['to'])).'\'';
        if (\date('H:i:s', \strtotime($_REQUEST['to'])) > '00:00:00') {
            //              $conditions .= ' AND created_at <= str_to_date(\''.date('Y-m-d H:i:s',strtotime($_REQUEST['to'])).'\', \'%Y-%m-%d %H:%i:%s\')';
            $conditions .= ' AND created_at <= \''.\date('Y-m-d H:i:s', \strtotime($_REQUEST['to'])).'\'';
        }
    }
    if (isset($_REQUEST['source']) && $_REQUEST['source']) {
        $conditions .= ' AND source_name=\''.\mysqli_real_escape_string($mysqli, $_REQUEST['source']).'\'';
    }
    if (isset($_REQUEST['checktype']) && $_REQUEST['checktype']) {
        $conditions .= ' AND checktype=\''.\mysqli_real_escape_string($mysqli, $_REQUEST['checktype']).'\'';
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

    $tmpview = 'REPORT_'.$userid.'_'.\time();

    $addfields = ('dates' == $type || 'months' == $type ? 'created_date,' : '')./* ($type=='sources'?'source_name,':'').($type=='clients'?'client_id,':'').($type=='users'?'user_id,':''). */ ('checktypes' == $type ? 'checktype,' : '');
    $addgroups = $addfields;
    $addfields .= ('hours' == $type ? 'hour(created_at) as hour,' : '');
    $addgroups .= ('hours' == $type ? 'hour,' : '');

    $viewtype = $type;
    if (isset($_REQUEST['checktype']) && $_REQUEST['checktype']) {
        $viewtype = 'checktypes';
    }
    if ('checktypes' != $type && isset($_REQUEST['source']) && $_REQUEST['source']) {
        $viewtype = 'sources';
    }

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
MAX(process_time) process_time,
SUM(1) total,
SUM(res_code<>500) success,
SUM(res_code=200) found,
SUM(res_code=500) error
FROM ResponseNew
WHERE res_code>0
$conditions
GROUP BY {$addgroups}
1,2,3,4,5
)
SQL;

    //      $mysqli->query($sql);

    $payfields = '';
    $field2 = '';
    $group2 = '';

    if ('users' == $type) {
        $field2 .= 'u.id user_id,';
        $group2 .= ',u.id';
    }
    if ('clients' == $type) {
        $field2 .= 'c.name,c.id client_id,';
        $group2 .= ',c.name,c.id';
    }

    // Группировка источник + параметр
    if ('sources' == $type) {
        $field .= ','.$fields['params'];
        $group2 .= ',2';
    }

    if ((isset($_REQUEST['pay']) && ('separate' == $_REQUEST['pay'] || 'pay' == $_REQUEST['pay'])) || (isset($_REQUEST['order']) && false !== \strpos($_REQUEST['order'], 'total'))) {
        if ('separate' == $_REQUEST['pay']) {
            $payfields .= <<<SQL
, SUM(r.success>0 AND (COALESCE(u.DefaultPrice,m.DefaultPrice)=0 OR source_name IN (SELECT source_name FROM UserSourcePrice WHERE (user_id=u.id OR user_id=m.id) AND price=0))) nonpay
, SUM(r.success>0 AND (COALESCE(u.DefaultPrice,m.DefaultPrice) IS NULL)) test
, SUM(r.success>0 AND (COALESCE(u.DefaultPrice,m.DefaultPrice,0)<>0 AND source_name NOT IN (SELECT source_name FROM UserSourcePrice WHERE (user_id=u.id OR user_id=m.id) AND price=0))) pay
SQL;
        }
        //          if ($type=='users' || $type=='clients' || $user_area<=1 || (isset($_REQUEST['user_id']) && $_REQUEST['user_id']) || (isset($_REQUEST['client_id']) && $_REQUEST['client_id']!="")) {
        if ('sources' == $type) {
            $field2 .= 'IFNULL((SELECT MIN(price) FROM UserSourcePrice WHERE (user_id=u.id OR user_id=m.id) AND source_name=r.source_name),IFNULL(u.DefaultPrice,m.DefaultPrice)) price,';
            $group2 .= ',price';
        }
        $field2 .= <<<SQL
SUM(CASE WHEN r.success>0 THEN IFNULL((SELECT MIN(price) FROM UserSourcePrice WHERE (user_id=u.id OR user_id=m.id) AND source_name=r.source_name),IFNULL(u.DefaultPrice,m.DefaultPrice)) ELSE 0 END) total,
SQL;
    }

    $sql = <<<SQL
SELECT
$field,
COUNT(DISTINCT r.request_id) reqcount,
COUNT(*) rescount,
SUM(r.success>0) success,
$field2
SUM(r.found>0) hit,
SUM(r.error>0) error,
AVG(r.process_time) process
$payfields
FROM $table r
JOIN SystemUsers u ON r.user_id=u.id
JOIN SystemUsers m ON m.id=CASE WHEN u.id=$userid OR u.MasterUserID IS NULL OR u.MasterUserID=0 OR u.AccessArea>0 THEN u.id ELSE u.MasterUserId END
LEFT JOIN Client c ON r.client_id=c.id
WHERE 1=1
$rep_conditions
GROUP BY 1$group2
SQL;

    /*
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
    FROM RequestSource r
    JOIN SystemUsers u ON r.user_id=u.id
    JOIN SystemUsers m ON m.id=CASE WHEN u.id=$userid OR u.MasterUserID IS NULL OR u.AccessArea>0 THEN u.id ELSE u.MasterUserId END
    LEFT JOIN Client c ON r.client_id=c.id
    WHERE 1=1
    $conditions
    $rep_conditions
    GROUP BY 1$group2
    SQL;
    */
    if (isset($_REQUEST['order'])) {
        $sql .= ' ORDER BY '.$_REQUEST['order'];
    }
    if ($user_level < 0 && isset($_REQUEST['debug'])) {
        echo $sql."\n\n";
    }

    $title = [
        'created_date' => 'Дата',
        'month' => 'Месяц',
        'hour' => 'Время',
        'source_name' => 'Источник',
        'start_param' => 'Параметр',
        'checktype' => 'Проверка',
        'login' => 'Логин',
        'code' => 'Код',
        'name' => 'Наименование',
        'reqcount' => 'Обращений',
        'rescount' => 'Запросов',
        'success' => 'Успешно',
//          "error" => "Ошибок",
        'nonpay' => 'Бесплатные',
        'test' => 'Тестовые',
        'pay' => 'Платные',
        'price' => 'Цена',
        'total' => 'Сумма',
        'hit' => 'Найдено',
        'process' => 'Ср.время,с',
        'successrate' => 'Успешно,%',
        'hitrate' => 'Найдено,%',
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
    ];
    if ($payfields > '' && 'checktypes' != $viewtype) {
        $hide[$viewtype]['error'] = 1;
    }

    $total = [
        'reqcount' => 0,
        'rescount' => 0,
        'success' => 0,
        'error' => 0,
        'pay' => 0,
        'nonpay' => 0,
        'test' => 0,
//          "hit" => 0,
        'total' => 0,
    ];

    $i = 0;

    $sqlRes = $mysqli->query($sql);
    $content = '<table border=1>';
    $first = [];
    while ($result = $sqlRes->fetch_assoc()) {
        //              print_r($result);
        if (0 == $i) {
            $first = $result;
            $content .= '<tr>';
            foreach ($result as $key => $val) {
                if (!isset($hide[$viewtype][$key]) && isset($title[$key])) {
                    $content .= '<th><b>'.$title[$key].'</b></th>';
                }
            }
            if ('sources' == $viewtype || 'checktypes' == $viewtype) {
                $content .= '<th><b>'.$title['successrate'].'</b></th>';
                $content .= '<th><b>'.$title['hitrate'].'</b></th>';
            }
            $content .= '</tr>';
        }
        $content .= '<tr>';
        foreach ($result as $key => $val) {
            if (!isset($hide[$viewtype][$key]) && isset($title[$key])) {
                if (isset($total[$key])) {
                    $total[$key] += $val;
                }
                $content .= '<td '.(\is_numeric($val) ? 'class="right"' : '').'>';
                $params = false;
                if ('created_date' == $key) {
                    $params = $_REQUEST;
                    $params['from'] = $val;
                    $params['to'] = $val;
                    $params['type'] = (!isset($_REQUEST['source']) || !$_REQUEST['source']) && (!isset($_REQUEST['checktype']) || !$_REQUEST['checktype']) ? 'sources' : (!isset($_REQUEST['user_id']) || !$_REQUEST['user_id'] ? 'users' : 'hours');
                }
                if ('hour' == $key && isset($_REQUEST['from']) && \strtotime($_REQUEST['from']) && ((isset($_REQUEST['to']) && \strtotime($_REQUEST['to']) && \date('d.m.Y', \strtotime($_REQUEST['from'])) == \date('d.m.Y', \strtotime($_REQUEST['to']))) || ((!isset($_REQUEST['to']) || !$_REQUEST['to']) && \date('d.m.Y', \strtotime($_REQUEST['from'])) == \date('d.m.Y')))) {
                    $params = $_REQUEST;
                    $params['from'] = \date('d.m.Y', \strtotime($_REQUEST['from'])).' '.$val.':00:00';
                    $params['to'] = \date('d.m.Y', \strtotime($_REQUEST['from'])).' '.$val.':59:59';
                    $params['type'] = (!isset($_REQUEST['source']) || !$_REQUEST['source']) && (!isset($_REQUEST['checktype']) || !$_REQUEST['checktype']) ? 'sources' : (!isset($_REQUEST['user_id']) || !$_REQUEST['user_id'] ? 'users' : 'hours');
                }
                if ('source_name' == $key || 'checktype' == $key) {
                    $params = $_REQUEST;
                    $params['source_name' == $key ? 'source' : 'checktype'] = $val;
                    $params['type'] = !isset($_REQUEST['from']) || !\strtotime($_REQUEST['from']) || !isset($_REQUEST['to']) || !\strtotime($_REQUEST['to']) || \date('d.m.Y', \strtotime($_REQUEST['from'])) != \date('d.m.Y', \strtotime($_REQUEST['to'])) ? 'dates' : (!isset($_REQUEST['user_id']) || !$_REQUEST['user_id'] ? 'users' : 'hours');
                }
                //                        if ($key=="start_param") {
                //                            $val = strtr($val,array('[0]'=>'','['=>' ',']'=>''));
                //                        }
                if ('code' == $key) {
                    $params = $_REQUEST;
                    $params['client_id'] = $result['client_id'];
                    $params['type'] = (!isset($_REQUEST['source']) || !$_REQUEST['source']) && (!isset($_REQUEST['checktype']) || !$_REQUEST['checktype']) ? 'sources' : (!isset($_REQUEST['from']) || !\strtotime($_REQUEST['from']) || !isset($_REQUEST['to']) || !\strtotime($_REQUEST['to']) || \date('d.m.Y', \strtotime($_REQUEST['from'])) != \date('d.m.Y', \strtotime($_REQUEST['to'])) ? 'dates' : (!isset($_REQUEST['user_id']) || !$_REQUEST['user_id'] ? 'users' : 'hours'));
                }
                if ('login' == $key) {
                    $params = $_REQUEST;
                    $params['user_id'] = $result['user_id'];
                    $params['type'] = (!isset($_REQUEST['source']) || !$_REQUEST['source']) && (!isset($_REQUEST['checktype']) || !$_REQUEST['checktype']) ? 'sources' : (!isset($_REQUEST['from']) || !\strtotime($_REQUEST['from']) || !isset($_REQUEST['to']) || !\strtotime($_REQUEST['to']) || \date('d.m.Y', \strtotime($_REQUEST['from'])) != \date('d.m.Y', \strtotime($_REQUEST['to'])) ? 'dates' : 'hours');
                }
                if ($params && 'hours' == $params['type'] && isset($params['from']) && \strtotime($params['from']) && ((isset($params['to']) && \strtotime($params['to']) && \date('d.m.Y H', \strtotime($params['from'])) == \date('d.m.Y H', \strtotime($params['to']) - 1)) || (!isset($params['to']) && \date('d.m.Y H', \strtotime($params['from'])) == \date('d.m.Y H')))) {
                    unset($params['type']);
                    unset($params['pay']);
                    unset($params['order']);
                    // $content .= '<a href="history.php?'.http_build_query($params).'">';
                } // elseif ($params)
                // $content .= '<a href="reports.php?'.http_build_query($params).'">';
                if ('process' == $key) {
                    $val = \number_format($val, 1, '.', '');
                }
                if (('price' == $key || 'total' == $key) && \strlen($val)) {
                    $val = \number_format($val, 2, '.', '');
                }
                $content .= $val;
                // if ($params) $content .= '</a>';
                $content .= '</td>';
            }
        }
        if ('sources' == $viewtype || 'checktypes' == $viewtype) {
            $content .= '<td class="right">'.($result['rescount'] > 10 ? \number_format($result['success'] / $result['rescount'] * 100, 2, '.', '') : '').'</td>';
            $content .= '<td class="right">'.($result['success'] > 10 ? \number_format($result['hit'] / $result['success'] * 100, 2, '.', '') : '').'</td>';
        }
        $content .= '</tr>';
        ++$i;
    }
    if ($i < 0) {
        $content .= 'Нет данных';
    } else {
        $content .= '<tr>';
        foreach ($first as $key => $val) {
            if (!isset($hide[$viewtype][$key]) && isset($title[$key])) {
                $content .= '<td class="right"><b>'.(isset($total[$key]) ? $total[$key] : '').'</b></td>';
            }
        }
        if ((isset($_REQUEST['source']) && $_REQUEST['source']) || (isset($_REQUEST['checktype']) && $_REQUEST['checktype'])) {
            $content .= '<td class="right"><b>'.($total['rescount'] > 10 ? \number_format($total['success'] / $total['rescount'] * 100, 2, '.', '') : '').'</b></td>';
            $content .= '<td class="right"><b>'.($total['success'] > 10 ? \number_format($total['hit'] / $total['success'] * 100, 2, '.', '') : '').'</b></td>';
        } elseif ('sources' == $viewtype) {
            $content .= '<td class="right"></td>';
            $content .= '<td class="right"></td>';
        }
        $content .= '</tr>';
    }
    $sqlRes->close();
    //      $mysqli->query("DROP VIEW $tmpview");
    $content .= '</table>';

    \file_put_contents($report_path.$_REQUEST['client_id'].'_'.$type.'.html', $content);

    if ('dates' == $type && $name && isset($total['total'])) {
        \file_put_contents($report_path.'total.csv', $_REQUEST['client_id'].";\"$name\";".\number_format($total['total'], 2, ',', '')."\n", \FILE_APPEND);
    }
}

function reportExcel($clientId, $name, $price): void
{
    global $report_path;

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($report_path.$clientId.'_sources.html');
    \unlink($report_path.$clientId.'_sources.html');
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->getHighestRow();
    foreach ($worksheet->getColumnIterator() as $key => $column) {
        if ('A' != $key) {
            //            $worksheet->getStyle($column->getColumnIndex())->getNumberFormat()->setFormatCode('#');
            //            $worksheet->getStyle($column->getColumnIndex())->getAlignment()->setHorizontal('right');
        }
        $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
    }
    /*
        $col_total = "B";
        $col_success = "C";
        $col_price = "D";
        $col_sum = "E";
    */
    // Группировка источник + параметр
    $col_total = 'C';
    $col_success = 'D';
    $col_price = 'E';
    $col_sum = 'F';

    $worksheet->getStyle("{$col_total}1:K1")->getAlignment()->setHorizontal('right');
    $worksheet->getStyle("{$col_price}2:{$col_sum}$rows")->getNumberFormat()->setFormatCode('0.00');
    $worksheet->setCellValue("A$rows", 'Итого');
    $worksheet->setCellValue("{$col_total}$rows", "=SUM({$col_total}2:{$col_total}".($rows - 1).')');
    $worksheet->setCellValue("{$col_success}$rows", "=SUM({$col_success}2:{$col_success}".($rows - 1).')');
    $worksheet->setCellValue("{$col_sum}$rows", "=SUM({$col_sum}2:{$col_sum}".($rows - 1).')');
    $worksheet->getStyle("A1:{$col_sum}$rows")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $worksheet->getStyle("A1:{$col_sum}$rows")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);
    $worksheet->getStyle("A$rows:{$col_sum}$rows")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);
    $worksheet->getStyle("A1:{$col_sum}1")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);
    //    $worksheet->mergeCells("A".($rows+2).":{$col_price}".($rows+2));
    //    $worksheet->setCellValue("A".($rows+2),'Сумма платежей за предыдущий период:');
    //    $worksheet->getStyle($col_sum.($rows+2))->getNumberFormat()->setFormatCode('0.00');
    //    $worksheet->setCellValue($col_sum.($rows+2),'0.00');
    //    $worksheet->mergeCells("A".($rows+3).":{$col_price}".($rows+3));
    //    $worksheet->setCellValue("A".($rows+3),'Стоимость обработки запроса:');
    //    $worksheet->getStyle($col_sum.($rows+3))->getNumberFormat()->setFormatCode('0.00');
    //    $worksheet->setCellValue($col_sum.($rows+3),number_format($price,2,'.',''));
    $worksheet->getStyle('A'.($rows + 4).":{$col_sum}".($rows + 4))->getFont()->setBold(true);
    $worksheet->mergeCells('A'.($rows + 4).":{$col_price}".($rows + 4));
    $worksheet->setCellValue('A'.($rows + 4), 'Итого к оплате:');
    $worksheet->getStyle($col_sum.($rows + 4))->getNumberFormat()->setFormatCode('0.00');
    $worksheet->setCellValue($col_sum.($rows + 4), "={$col_sum}$rows");
    $worksheet->setTitle('По источникам');

    if (\file_exists($report_path.$clientId.'_dates.html')) {
        $spreadsheet2 = \PhpOffice\PhpSpreadsheet\IOFactory::load($report_path.$clientId.'_dates.html');
        \unlink($report_path.$clientId.'_dates.html');
        $worksheet = $spreadsheet2->getActiveSheet();
        $rows = $worksheet->getHighestRow();
        foreach ($worksheet->getColumnIterator() as $key => $column) {
            $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
        /*
                $worksheet->getStyle("E2:E$rows")->getNumberFormat()->setFormatCode('0.00');
                $worksheet->getStyle("A1:E$rows")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $worksheet->getStyle("A1:E$rows")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);
                $worksheet->getStyle("A$rows:E$rows")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);
                $worksheet->getStyle("A1:E1")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);
                $worksheet->getStyle("B1:E1")->getAlignment()->setHorizontal('center');
        */
        $worksheet->setCellValue("A$rows", 'Итого');
        $worksheet->setCellValue("B$rows", '=SUM(B2:B'.($rows - 1).')');
        $worksheet->setCellValue("C$rows", '=SUM(C2:C'.($rows - 1).')');
        $worksheet->setCellValue("D$rows", '=SUM(D2:D'.($rows - 1).')');
        $worksheet->setCellValue("E$rows", '=SUM(E2:E'.($rows - 1).')');
        $worksheet->setTitle('По датам');
        $spreadsheet->addSheet($worksheet);
    }

    if (\file_exists($report_path.$clientId.'_users.html')) {
        $spreadsheet3 = \PhpOffice\PhpSpreadsheet\IOFactory::load($report_path.$clientId.'_users.html');
        \unlink($report_path.$clientId.'_users.html');
        $worksheet = $spreadsheet3->getActiveSheet();
        $rows = $worksheet->getHighestRow();
        foreach ($worksheet->getColumnIterator() as $key => $column) {
            $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
        /*
                $worksheet->getStyle("E2:E$rows")->getNumberFormat()->setFormatCode('0.00');
                $worksheet->getStyle("A1:E$rows")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $worksheet->getStyle("A1:E$rows")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);
                $worksheet->getStyle("A$rows:E$rows")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);
                $worksheet->getStyle("A1:E1")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);
                $worksheet->getStyle("B1:E1")->getAlignment()->setHorizontal('center');
        */
        $worksheet->setCellValue("A$rows", 'Итого');
        $worksheet->setCellValue("B$rows", '=SUM(B2:B'.($rows - 1).')');
        $worksheet->setCellValue("C$rows", '=SUM(C2:C'.($rows - 1).')');
        $worksheet->setCellValue("D$rows", '=SUM(D2:D'.($rows - 1).')');
        $worksheet->setCellValue("E$rows", '=SUM(E2:E'.($rows - 1).')');
        $worksheet->setTitle('По пользователям');
        $spreadsheet->addSheet($worksheet);
    }
    $writer = new Xlsx($spreadsheet);
    $writer->save($report_path.$name.'.xlsx');
    echo $report_path.$name.".xlsx\n";
}

$mysqli = \mysqli_connect($database['server'], $database['login'], $database['password'], $database['name']) || exit(\mysqli_errno($db).': '.\mysqli_error($db));
if ($mysqli) {
    \mysqli_query($mysqli, 'Set character set utf8');
    \mysqli_query($mysqli, "Set names 'utf8'");
}

/*
INSERT INTO UserSourcePrice
SELECT id,NULL,'Boards',1 FROM SystemUsers
WHERE DefaultPrice>0
AND id not in (select user_id from UserSourcePrice)
AND ClientId IN (SELECT id FROM `Client` WHERE TariffId=7)
UNION
SELECT id,NULL,'Names',1 FROM SystemUsers
WHERE DefaultPrice>0
AND id not in (select user_id from UserSourcePrice)
AND ClientId IN (SELECT id FROM `Client` WHERE TariffId=7)
UNION
SELECT id,NULL,'Phones',1 FROM SystemUsers
WHERE DefaultPrice>0
AND id not in (select user_id from UserSourcePrice)
AND ClientId IN (SELECT id FROM `Client` WHERE TariffId=7)
;

INSERT INTO UserSourcePrice
SELECT id,NULL,'Boards',1.5 FROM SystemUsers
WHERE DefaultPrice>0
AND id not in (select user_id from UserSourcePrice)
AND ClientId IN (SELECT id FROM `Client` WHERE TariffId=8)
UNION
SELECT id,NULL,'Names',1.5 FROM SystemUsers
WHERE DefaultPrice>0
AND id not in (select user_id from UserSourcePrice)
AND ClientId IN (SELECT id FROM `Client` WHERE TariffId=8)
UNION
SELECT id,NULL,'Phones',1.5 FROM SystemUsers
WHERE DefaultPrice>0
AND id not in (select user_id from UserSourcePrice)
AND ClientId IN (SELECT id FROM `Client` WHERE TariffId=8)
;
*/

/*
UPDATE ResponseNew
SET res_code=500
WHERE user_id=2967 AND source_name='whatsapp'
AND process_time>=9
AND created_date>='2023-03-01' AND created_date<'2023-04-01'
;

UPDATE ResponseNew
SET res_code=500
WHERE user_id=2967 AND source_name='whatsapp'
AND request_id IN (SELECT id FROM RequestNew WHERE user_id=2967 AND created_date>='2023-03-01' AND created_date<'2023-04-01' AND unix_timestamp(processed_at)-unix_timestamp(created_at)>=9)
AND created_date>='2023-03-01' AND created_date<'2023-04-01'
;
*/

$types = ['sources', 'dates', 'users'];
$periods = [
//    'январь 2022' => array('from' => '01.01.2022', 'to' => '31.01.2022'),
//    'февраль 2022' => array('from' => '01.02.2022', 'to' => '28.02.2022'),
//    'март 2022' => array('from' => '01.03.2022', 'to' => '31.03.2022'),
//    'апрель 2022' => array('from' => '01.04.2022', 'to' => '30.04.2022'),
//    'май 2022' => array('from' => '01.05.2022', 'to' => '31.05.2022'),
//    'июнь 2022' => array('from' => '01.06.2022', 'to' => '30.06.2022'),
//    'июль 2022' => array('from' => '01.07.2022', 'to' => '31.07.2022'),
//    'август 2022' => array('from' => '01.08.2022', 'to' => '31.08.2022'),
//    'сентябрь 2022' => array('from' => '01.09.2022', 'to' => '30.09.2022'),
//    'октябрь 2022' => array('from' => '01.10.2022', 'to' => '31.10.2022'),
//    'ноябрь 2022' => array('from' => '01.11.2022', 'to' => '30.11.2022'),
//    'декабрь 2022' => array('from' => '01.12.2022', 'to' => '31.12.2022'),
//    'январь 2023' => array('from' => '01.01.2023', 'to' => '31.01.2023'),
//    'февраль 2023' => array('from' => '01.02.2023', 'to' => '28.02.2023'),
    'март 2023' => ['from' => '01.03.2023', 'to' => '31.03.2023'],
//    '1кв 2022' => array('from' => '01.01.2022', 'to' => '31.03.2022'),
//    '2кв 2022' => array('from' => '01.04.2022', 'to' => '30.06.2022'),
//    '3кв 2022' => array('from' => '01.07.2022', 'to' => '30.09.2022'),
//    '4кв 2022' => array('from' => '01.10.2022', 'to' => '31.12.2022'),
//    '2п 2022' => array('from' => '01.07.2022', 'to' => '31.12.2022'),
//    '2022' => array('from' => '01.01.2022', 'to' => '31.12.2022'),
];
$clientfilter = " AND Code IN ('boostra','blanc','gpbl','garnet24','potok','prima-inform')";
$clientfilter .= ' AND TariffID IN (3,4,7,8,11)'; // месяц
// $clientfilter = " AND TariffID IN (6,10,13,14)"; // квартал
// $clientfilter = " AND TariffID IN (9)"; // полугодие
// $clientfilter = " AND TariffID IN (15)"; // год

foreach ($periods as $name => $period) {
    $_REQUEST = $period;
    $clients = $mysqli->query("SELECT Client.*,(SELECT MAX(DefaultPrice) FROM SystemUsers WHERE ClientID=Client.id AND (MasterUserID IS NULL OR MasterUserID=0)) Price FROM Client WHERE Status>=0 $clientfilter ORDER BY code");
    while ($client = $clients->fetch_assoc()) {
        if ($client['Price'] > 0) {
            $_REQUEST['client_id'] = $client['id'];
            $_REQUEST['user_id'] = '';
            //             $_REQUEST['nested'] = 0;
            $_REQUEST['pay'] = 'pay';
            //             $_REQUEST['debug'] = 1;
            foreach ($types as $type) {
                $_REQUEST['order'] = ('sources' == $type ? 'price,' : '').'1';
                report($type, $client['Name']);
            }
            reportExcel($client['id'], "Отчет по запросам {$client['Name']} ({$client['Code']}) - $name", $client['Price']);

            if ('zaymer' == $client['Code']) {
                /*
                                 $_REQUEST['user_id'] = '2757,2758,2759,2760,2761,2762,2763,3514,3535,3612,3626,3627';
                                 report('sources');
                                 reportExcel($client['id'],"Отчет по запросам {$client['Name']} ({$client['Code']}) zaymer0x - $name",$client['Price']);
                */
                $_REQUEST['user_id'] = '3178';
                report('sources');
                reportExcel($client['id'], "Отчет по запросам {$client['Name']} ({$client['Code']}) zaymer_api - $name", $client['Price']);

                $_REQUEST['user_id'] = '3491';
                report('sources');
                reportExcel($client['id'], "Отчет по запросам {$client['Name']} ({$client['Code']}) zaymer_api2 - $name", $client['Price']);
            }

            if ('lockobank' == $client['Code']) {
                $_REQUEST['user_id'] = '596';
                report('sources');
                reportExcel($client['id'], "Отчет по запросам {$client['Name']} ({$client['Code']}) lockobank2 - $name", $client['Price']);

                $_REQUEST['user_id'] = '1080';
                report('sources');
                reportExcel($client['id'], "Отчет по запросам {$client['Name']} ({$client['Code']}) lockobank_prod - $name", $client['Price']);

                $_REQUEST['user_id'] = '1079';
                report('sources');
                reportExcel($client['id'], "Отчет по запросам {$client['Name']} ({$client['Code']}) lockobank_test - $name", $client['Price']);
            }
        }
    }
}

$mysqli->close();
