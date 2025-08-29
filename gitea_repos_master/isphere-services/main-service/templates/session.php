<?php

include 'config.php';
include 'auth.php';

$user_access = get_user_access($mysqli);
if (!$user_access['stats']) {
    echo 'У вас нет доступа к этой странице';
    return;
}

$user_level = get_user_level($mysqli);
$user_sources = get_user_sources($mysqli);

echo '<link rel="stylesheet" type="text/css" href="public/main.css"/>';
echo '<h1>Новая сессия</h1><hr/><a href="stats.php">Назад</a><br/><br/>';

?>
<form id="checkform" method="POST">
    <table>
        <tr>
            <td>Источник:</td>
            <td>
            <select name="source">
<?php
    $select = 'SELECT DISTINCT id,code FROM source WHERE status=0 ORDER BY 2';
$sqlRes = $mysqli->query($select);
while ($result = $sqlRes->fetch_assoc()) {
    echo '<option value="'.$result['code'].'"'.(isset($_REQUEST['source']) && ($result['id'] == $_REQUEST['source'] || $result['code'] == $_REQUEST['source']) ? ' selected' : '').'>'.$result['code'].'</option>';
}
$sqlRes->close();
?>
            </select>
            </td>
        </tr>
        <tr>
            <td>Токен:</td>
            <td>
                <input type="text" name="token" value="" maxlength="250"/>
            </td>
        </tr>
        <tr>
            <td>Ключ шифрования:</td>
            <td>
                <input type="text" name="enckey" value="" maxlength="250"/>
            </td>
        </tr>
        <tr>
            <td>Устройство:</td>
            <td>
                <input type="text" name="device" value="" maxlength="250"/>
            </td>
        </tr>
        <tr>
            <td>Cookies:</td>
            <td>
                <textarea name="cookies" cols="100" rows="5"></textarea>
            </td>
        </tr>
        <tr>
            <td>Данные:</td>
            <td>
                <textarea name="data" cols="100" rows="5"></textarea>
            </td>
        </tr>
        <tr>
            <td>Прокси:</td>
            <td>
            <select name="proxy">
<?php
    echo '<option value="null"'.(!isset($_REQUEST['proxy']) ? ' selected' : '').'>Нет</option>';
$select = 'SELECT DISTINCT id,server FROM proxy WHERE status=1 ORDER BY 2';
$sqlRes = $mysqli->query($select);
while ($result = $sqlRes->fetch_assoc()) {
    echo '<option value="'.$result['server'].'"'.(isset($_REQUEST['proxy']) && ($result['id'] == $_REQUEST['proxy'] || $result['server'] == $_REQUEST['proxy']) ? ' selected' : '').'>'.$result['server'].'</option>';
}
$sqlRes->close();
?>
            </select>
            </td>
        </tr>
        <tr>
            <td>Сервер (хост):</td>
            <td>
                <input type="text" name="server" value="" maxlength="250"/>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <input name="operation" type="hidden" value="add"/>
                <input id="submitbutton" type="submit" value="Добавить"/>
            </td>
        </tr>
    </table>
</form>

<hr/>

<?php

if (isset($_REQUEST['operation']) && 'add' == $_REQUEST['operation'] && isset($_REQUEST['source'])) {
    if (!isset($_REQUEST['token'])) {
        $_REQUEST['token'] = '';
    }
    if (!isset($_REQUEST['enckey'])) {
        $_REQUEST['enckey'] = '';
    }
    if (!isset($_REQUEST['device'])) {
        $_REQUEST['device'] = '';
    }
    if (!isset($_REQUEST['cookies'])) {
        $_REQUEST['cookies'] = '';
    }
    if (!isset($_REQUEST['data'])) {
        $_REQUEST['data'] = '';
    }
    if (!isset($_REQUEST['proxy'])) {
        $_REQUEST['proxy'] = 'null';
    }
    if (!isset($_REQUEST['server'])) {
        $_REQUEST['server'] = '';
    }

    if (!\is_numeric($_REQUEST['source'])) {
        $select = "SELECT id FROM source WHERE code='{$_REQUEST['source']}'";
        $sqlRes = $mysqli->query($select);
        if ($result = $sqlRes->fetch_assoc()) {
            $_REQUEST['source'] = $result['id'];
        } else {
            echo 'Источник не найден<br/>';
            $_REQUEST['source'] = 0;
        }
        $sqlRes->close();
    }

    if (isset($_REQUEST['proxy']) && ('null' != $_REQUEST['proxy']) && !\is_numeric($_REQUEST['proxy'])) {
        $select = "SELECT id FROM proxy WHERE server='{$_REQUEST['proxy']}'";
        $sqlRes = $mysqli->query($select);
        if ($result = $sqlRes->fetch_assoc()) {
            $_REQUEST['proxy'] = $result['id'];
        } else {
            echo 'Прокси не найден<br/>';
            $_REQUEST['proxy'] = 'null';
        }
        $sqlRes->close();
    }

    $sql = <<<SQL
    INSERT INTO session (sourceid,sessionstatusid,token,enckey,device,cookies,data,proxyid,server) VALUES ({$_REQUEST['source']},2,'{$_REQUEST['token']}','{$_REQUEST['enckey']}','{$_REQUEST['device']}','{$_REQUEST['cookies']}','{$_REQUEST['data']}',{$_REQUEST['proxy']},'{$_REQUEST['server']}');
SQL;

    $mysqli->query($sql);
    $resId = $mysqli->insert_id;

    if ($resId) {
        echo "Сессия $resId успешно добавлена<br/>";
    } else {
        echo 'Ошибка добавления сессии<br/>';
    }
}

include 'footer.php';
