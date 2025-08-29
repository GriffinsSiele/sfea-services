<?php

/**
 * @global EntityManagerInterface $entityManager
 * @global Connection $connection
 * @global PhpEngine $view
 * @global Request $request
 * @global SystemUser $user
 */

use App\Controller\ShowResultController;
use App\Entity\SystemUser;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\PhpEngine;

require_once 'functions.php';

$mainRequest = $request;

$view->extend('base.php');
$view['slots']->set('title', 'История запросов');
$view['slots']->set('root-container-class', 'container-fluid');
$view['slots']->set('article-class', 'history');

$userid = $user->getId();
$user_level = $user->getAccessLevel();
$user_sources = $user->getAccessSourcesMap();
$clientid = $user->getClient()?->getId();

$conditions = '';
$join = '';
$order = $user->getAccessArea() >= 4 && isset($_REQUEST['order'])
    ? $connection->createQueryBuilder()->expr()->literal($_REQUEST['order'])
    : 'id DESC';
$limit = $user->getAccessArea() >= 4 && isset($_REQUEST['limit'])
    ? (int)($_REQUEST['limit'])
    : 20;
$users = '';
$users_list = '';


if (!isset($_REQUEST['from'])) {
    $_REQUEST['from'] = \date('d.m.Y');
}

?>
<div class="container-fluid">
    <div class="row">
        <div class="col">
            <form action="">
                <div class="row align-items-center">
                    <?php
                    $select = 'SELECT Id, Code FROM Client';
                    if ($user->getAccessArea() < 4) {
                        $select .= " WHERE Id=$clientid";
                        if ($user->getAccessArea() >= 3) {
                            $select .= " OR MasterUserId=$userid";
                        }
                    }
                    $select .= ' ORDER BY Code';
                    $sqlRes = $connection->executeQuery($select);
                    $clients = '';
                    if ($user->getAccessArea() >= 3 && $sqlRes->rowCount() > 0) {
                        $clients .= '<div class="col-auto">';
                        $clients .= '<select class="form-select form-select-sm" name="client_id"><option value="">Все клиенты</option>';
                        $clients .= '<option value="0"' . (isset($_REQUEST['client_id']) && '0' === $_REQUEST['client_id'] ? ' selected' : '') . '>Без договора</option>';
                        while ($result = $sqlRes->fetchAssociative()) {
                            $clients .= '<option value="' . $result['Id'] . '"' . (isset($_REQUEST['client_id']) && $result['Id'] == $_REQUEST['client_id'] ? ' selected' : '') . '>' . $result['Code'] . '</option>';
                        }
                        $clients .= '</select>';
                        $clients .= '</div>';
                    }
                    if ($user->getAccessArea() >= 3) {
                        echo $clients;
                    } else {
                        $_REQUEST['client_id'] = $clientid;
                    }

                    $select = 'SELECT Id, Login, Locked FROM SystemUsers';
                    if ($user->getAccessArea() < 4) {
                        $select .= " WHERE Id=$userid";
                        if ($user->getAccessArea() >= 1) {
                            $select .= " OR MasterUserId=$userid";
                        }
                        if ($user->getAccessArea() >= 2) {
                            $select .= " OR ClientId=$clientid";
                        }
                        if ($user->getAccessArea() >= 3) {
                            $select .= " OR ClientId IN (SELECT id FROM Client WHERE MasterUserId=$userid)";
                        }
                    }
                    $select .= ' ORDER BY Login';
                    $sqlRes = $connection->executeQuery($select);
                    $users = '';
                    if ($sqlRes->rowCount() > 1) {
                        $users .= '<div class="col-auto">';
                        $users .= ' <select class="form-select form-select-sm" name="user_id"><option value="">Все пользователи</option>';
                        while ($result = $sqlRes->fetchAssociative()) {
                            $users .= '<option value="' . $result['Id'] . '"' . (isset($_REQUEST['user_id']) && $result['Id'] == $_REQUEST['user_id'] ? ' selected' : '') . '>' . $result['Login'] . ($result['Locked'] ? ' (-)' : '') . '</option>';
                            $users_list .= ($users_list ? ',' : '') . $result['Id'];
                        }
                        $users .= '</select>';
                        $users .= '</div>';
                        if ($user->getAccessArea() < 4) {
                            $conditions .= ' AND user_id IN (' . $users_list . ')';
                        }
                    } else {
                        $_REQUEST['user_id'] = $userid;
                    }

                    //      if ($users || ($user_level<0)) {
                    echo $users;
                    if ($user->getAccessArea() >= 2) {
                        ?>
                        <div class="col-auto">
                            <div class="form-check">
                                <input id="nested" class="form-check-input" type="checkbox"
                                       name="nested"<?= $_REQUEST['nested'] ?? false ? ' checked="checked"' : '' ?>/>
                                <label for="nested" class="form-check-label">+дочерние</label>
                            </div>
                        </div>
                        <?php
                        if (isset($_REQUEST['limit']) && $limit) {
                            echo '<input type="hidden" name="limit" value="' . $limit . '">';
                        }
                    }
                    //          if ($user_level<0) {
                    ?>
                    <div class="col-auto">
                        <div class="row g-2 align-items-center">
                            <div class="col-auto">
                                <label class="col-form-label" for="from">Период с</label>
                            </div>
                            <div class="col-auto">
                                <input size="4" class="form-control form-control-sm" id="from" name="from" value="<?= (isset($_REQUEST['from']) ? $_REQUEST['from'] : '') ?>"/>
                            </div>
                            <div class="col-auto">
                                <label class="col-form-label" for="to">по</label>
                            </div>
                            <div class="col-auto">
                                <input size="4" class="form-control form-control-sm" id="to" name="to" value="<?= (isset($_REQUEST['to']) ? $_REQUEST['to'] : '') ?>"/>
                            </div>
                        </div>
                    </div>
                    <?php

                    if ($user_level < 0) {
                        $select = 'SELECT DISTINCT source_name FROM ResponseNew ORDER BY 1';
                        echo '<div class="col-auto">';
                        echo '<select class="form-select form-select-sm" name="source"><option value="">Все источники</option>';
                        $sqlRes = $connection->executeQuery($select);
                        while ($result = $sqlRes->fetchAssociative()) {
                            echo '<option value="' . $result['source_name'] . '"' . (isset($_REQUEST['source']) && $result['source_name'] == $_REQUEST['source'] ? ' selected' : '') . '>' . $result['source_name'] . '</option>';
                        }
                        echo '</select>';
                        echo '</div>';
                    }

                    if ($user_level < 0) {
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
                    }

                    if ($user_level < 0) {
                        echo '<div class="col-auto">';
                        echo '<select class="form-select form-select-sm" name="res_code">';
                        echo '<option value=""' . (!isset($_REQUEST['res_code']) || !$_REQUEST['res_code'] ? ' selected' : '') . '>Все результаты</option>';
                        echo '<option value="200"' . (isset($_REQUEST['res_code']) && '200' == $_REQUEST['res_code'] ? ' selected' : '') . '>Найден</option>';
                        echo '<option value="204"' . (isset($_REQUEST['res_code']) && '204' == $_REQUEST['res_code'] ? ' selected' : '') . '>Не найден</option>';
                        echo '<option value="500"' . (isset($_REQUEST['res_code']) && '500' == $_REQUEST['res_code'] ? ' selected' : '') . '>Ошибка</option>';
                        echo '</select>';
                        echo '</div>';
                    }
                    /*
                              if ($user_level<0) {
                                  echo ' Поиск <input type="text" name="find" value="'.(isset($_REQUEST['find'])?$_REQUEST['find']:'').'">';
                              }
                    */
                    echo '<div class="col-auto">';
                    echo ' <input class="btn btn-primary btn-sm" type="submit" value="Обновить"></form>';
                    echo '</div>';
                    echo '</div>'; // .row
                    echo '</form>'; // form
                    echo '</div>'; // .col
                    echo '</div>'; // .row
                    echo '</div>'; // .container-fluid
                    //      }
                    /*
                          if(isset($_REQUEST['find']) && strlen($_REQUEST['find'])){
                              $conditions .= " AND locate('".mysqli_real_escape_string($mysqli,$_REQUEST['find'])."',r.request)>0";
                          }
                    */
                    if (isset($_REQUEST['user_id']) && 0 != (int)$_REQUEST['user_id']) {
                        $conditions .= ' AND (user_id=' . (int)$_REQUEST['user_id'] . (isset($_REQUEST['nested']) && $_REQUEST['nested'] ? ' OR user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId=' . (int)$_REQUEST['user_id'] . ')' : '') . ')';
                    }
                    if (isset($_REQUEST['client_id']) && 0 != (int)$_REQUEST['client_id']) {
                        $conditions .= ' AND client_id=' . (int)$_REQUEST['client_id'];
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
                        $conditions .= ' AND created_date >= str_to_date(\'' . \date(
                                'Y-m-d',
                                \strtotime($_REQUEST['from'])
                            ) . '\', \'%Y-%m-%d\')';
                        if (\date('H:i:s', \strtotime($_REQUEST['from'])) > '00:00:00') {
                            $conditions .= ' AND created_at >= str_to_date(\'' . \date(
                                    'Y-m-d H:i:s',
                                    \strtotime($_REQUEST['from'])
                                ) . '\', \'%Y-%m-%d %H:%i:%s\')';
                        }
                    }
                    if (isset($_REQUEST['to']) && \strtotime($_REQUEST['to'])) {
                        $conditions .= ' AND created_date <= str_to_date(\'' . \date(
                                'Y-m-d',
                                \strtotime($_REQUEST['to'])
                            ) . '\', \'%Y-%m-%d\')';
                        if (\date('H:i:s', \strtotime($_REQUEST['to'])) > '00:00:00') {
                            $conditions .= ' AND created_at <= str_to_date(\'' . \date(
                                    'Y-m-d H:i:s',
                                    \strtotime($_REQUEST['to'])
                                ) . '\', \'%Y-%m-%d %H:%i:%s\')';
                        }
                    }
                    if (isset($_REQUEST['minid'])) {
                        $conditions .= ' AND id < ' . (int)$_REQUEST['minid'];
                    }
                    if (isset($_REQUEST['maxid'])) {
                        $conditions .= ' AND id > ' . (int)$_REQUEST['maxid'];
                    }

                    $response_conditions = '';
                    if (isset($_REQUEST['source']) && $_REQUEST['source']) {
                        $response_conditions .= ' AND res_code>0 AND source_name = ' .
                            $connection->createQueryBuilder()->expr()->literal($_REQUEST['source']);
                    }
                    if (isset($_REQUEST['checktype']) && $_REQUEST['checktype']) {
                        $response_conditions .= ' AND res_code>0 AND checktype = ' .
                            $connection->createQueryBuilder()->expr()->literal($_REQUEST['checktype']);
                    }
                    if (isset($_REQUEST['res_code']) && (int)$_REQUEST['res_code']) {
                        $response_conditions .= ' AND res_code=' . (int)$_REQUEST['res_code'];
                    }
                    if ($response_conditions) {
                        $conditions .= ' AND r.id IN (SELECT request_id id FROM ResponseNew WHERE 1=1 ' . \strtr(
                                $conditions,
                                ['AND id' => 'AND request_id']
                            ) . " $response_conditions)";
                    }

                    //      $select = "SELECT r.*,u.login FROM RequestNew r, SystemUsers u $join WHERE r.user_id=u.Id $conditions ORDER BY $order LIMIT $limit";
                    $select = "SELECT r.*,(SELECT Login FROM SystemUsers WHERE id=r.user_id) login FROM RequestNew r WHERE 1=1 $conditions ORDER BY $order LIMIT $limit";
                    //      echo "$select<br/><br/>";
                    $sqlRes = $connection->executeQuery($select);
                    $minid = $_REQUEST['minid'] ?? 1000000000;
                    $maxid = $_REQUEST['maxid'] ?? 0;

                    echo '<div class="container-fluid">';
                    echo '  <div class="row">';
                    echo '    <div class="col">';
                    echo '      <div class="card">';
                    echo '        <div class="card-body">';
                    echo '          <div class="card-text">';
                    if (!$sqlRes) {
                        echo "Ошибка при выполнении запроса\n";
                    } elseif ($sqlRes->rowCount()) {
                        echo <<<'HTML'
<table class="table">
<thead>
</thead>
<tbody>
HTML;
                    } else {
                        echo "Запросов не найдено\n";
                    }
                    while ($sqlRes && ($result = $sqlRes->fetchAssociative())) {
                        if ($maxid < $result['id']) {
                            $maxid = $result['id'];
                        }
                        if ($minid > $result['id']) {
                            $minid = $result['id'];
                        }
                        //              print_r($result);
                        echo "<tr>\n";
                        echo '<td data-name="id">' . $result['id'] . '</td>';
                        echo '<td data-name="external_id">' . $result['external_id'] . '</td>';
                        echo '<td data-name="created_at">' . $result['created_at'] . '</td>';
                        $result['request'] = '';
                        $numName = \str_pad($result['id'], 9, '0', \STR_PAD_LEFT);
                        $titles = \str_split($numName, 3);
                        if (\file_exists(
                            $xmlpath . $titles[0] . '/' . $titles[1] . '/' . $titles[2] . '_req.xml'
                        )) {
                            $result['request'] = \file_get_contents(
                                $xmlpath . $titles[0] . '/' . $titles[1] . '/' . $titles[2] . '_req.xml'
                            );
                        } elseif (\file_exists($xmlpath . $titles[0] . '/' . $titles[1] . '.tar.gz')) {
                            $result['request'] = \shell_exec(
                                'tar xzfO ' . $xmlpath . $titles[0] . '/' . $titles[1] . '.tar.gz ' . $titles[2] . '_req.xml'
                            );
                        }
                        $result['request'] = \preg_replace(
                            "/<\?xml[^>]+>/",
                            '',
                            \substr($result['request'], \strpos($result['request'], '<'))
                        );
                        $request = \simplexml_load_string($result['request']);
                        echo '<td data-name="login">' . $result['login'] . "</td>\n";
                        echo '<td data-name="type">' . ($result['type'] ?: 'api') . "</td>\n";
                        echo '<td data-name="ip">' . $result['ip'] . "</td>\n";
                        echo '<td data-name="sources">' . (isset($request->sources) ? \strtr(
                                $request->sources,
                                [' ' => '', "\u{a0}" => '', ',' => '<br/>']
                            ) : (isset($request->PersonReq->sources) ? \strtr(
                                $request->PersonReq->sources,
                                [',' => '<br/>']
                            ) : '')) . "</td>\n";
                        echo '<td data-name="params">';
                        if (!$request) {
                            echo '<span class="text-muted"><i class="fa fa-solid fa-triangle-exclamation"></i> Данные запроса недоступны</span>';
                        }
                        if (isset($request->PersonReq)) {
                            $prequest = \json_decode(\json_encode($request->PersonReq), true);
                            foreach ($prequest as $key => $val) {
                                if ($val && !\is_array($val) && !\in_array(
                                        $key,
                                        ['UserID', 'Password', 'requestId', 'sources']
                                    )) {
                                    echo $key . ': ' . $val . '<br />';
                                }
                            }
                        }
                        if (isset($request->PhoneReq)) {
                            foreach ($request->PhoneReq as $req) {
                                echo $req->phone . '<br />';
                            }
                        }
                        if (isset($request->EmailReq)) {
                            foreach ($request->EmailReq as $req) {
                                echo $req->email . '<br />';
                            }
                        }
                        if (isset($request->SkypeReq)) {
                            foreach ($request->SkypeReq as $req) {
                                echo $req->skype . '<br />';
                            }
                        }
                        if (isset($request->NickReq)) {
                            foreach ($request->NickReq as $req) {
                                echo $req->nick . '<br />';
                            }
                        }
                        if (isset($request->URLReq)) {
                            foreach ($request->URLReq as $req) {
                                echo $req->url . '<br />';
                            }
                        }
                        if (isset($request->CarReq)) {
                            $prequest = \json_decode(\json_encode($request->CarReq), true);
                            foreach ($prequest as $key => $val) {
                                if ($val && !\is_array($val)) {
                                    echo $key . ': ' . $val . '<br />';
                                }
                            }
                        }
                        if (isset($request->IPReq)) {
                            foreach ($request->IPReq as $req) {
                                echo $req->ip . '<br />';
                            }
                        }
                        if (isset($request->OrgReq)) {
                            $prequest = \json_decode(\json_encode($request->OrgReq), true);
                            foreach ($prequest as $key => $val) {
                                if ($val && !\is_array($val)) {
                                    echo $key . ': ' . $val . '<br />';
                                }
                            }
                        }
                        if (isset($request->OtherReq)) {
                            $prequest = \json_decode(\json_encode($request->OtherReq), true);
                            foreach ($prequest as $key => $val) {
                                if ($val && !\is_array($val)) {
                                    echo $key . ': ' . $val . '<br />';
                                }
                            }
                        }
                        if (isset($request->CardReq)) {
                            foreach ($request->CardReq as $req) {
                                echo $req->card . '<br />';
                            }
                        }
                        echo '</td>';
                        //		$response = simplexml_load_string($recsult['response']);
                        echo '<td data-name="actions">';
                        if ($request) {
                            ?>
                            <a href="<?= $urlGenerator->generate(ShowResultController::NAME, [
                                'id' => $result['id'],
                            ]); ?>" target="_blank">Просмотр</a>

                            <br/>

                            <a href="<?= $urlGenerator->generate(ShowResultController::NAME, [
                                'id' => $result['id'],
                                'mode' => 'pdf'
                            ]); ?>" target="_blank">PDF</a>

                            <a href="<?= $urlGenerator->generate(ShowResultController::NAME, [
                                'id' => $result['id'],
                                'mode' => 'xml'
                            ]); ?>" target="_blank">XML</a>
                            <?php
                        } else {
                            echo '<span class="text-muted"><i class="fa fa-solid fa-triangle-exclamation"></i> Результаты обработки отсутствуют</span>';
                        }
                        echo '</td>';
                        echo "</tr>\n";
                    }
                    if ($sqlRes && $sqlRes->rowCount()) {
                        echo "</tbody></table>\n";
                    }
                    echo '</div>'; // .card-text
                    $querystr = \preg_replace("/\&m(in|ax)id=\d+/", '', \getenv('QUERY_STRING'));
                    echo '<a class="card-link" href="/history?' . $querystr . '&maxid=' . ($maxid ?: '') . '"><i class="fa fa-solid fa-angle-left"></i> Новее</a> ';
                    if ($sqlRes && ($sqlRes->rowCount() == $limit)) {
                        echo '<a class="card-link" href="/history?' . $querystr . '&minid=' . $minid . '">Старее <i class="fa fa-solid fa-angle-right"></i></a>';
                    }
                    echo '</div>'; // .card-body
                    echo '</div>'; // .card
                    echo '</div>'; // .col
                    echo '</div>'; // .row
                    echo '</div>'; // .container-fluid
                    ?>
                </div>
        </div>
    </div>
