<?php

function get_sites($mysqli)
{
    $sites = [];
    if ($result = \mysqli_query($mysqli, 'SELECT * FROM Site')) {
        while ($row = $result->fetch_assoc()) {
            $sites[$row['host']] = $row;
        }
        \mysqli_free_result($result);
    }

    return $sites;
}

function get_user_id($mysqli)
{
    $sites = get_sites($mysqli);
    if (isset($_SESSION['userid'])) {
        return $_SESSION['userid'];
    }
    $userid = false;
    if (isset($user->getUserIdentifier()) && isset($user->getPassword()) && ($result = \mysqli_query($mysqli, 'SELECT * FROM SystemUsers WHERE `Login`=\''.\mysqli_real_escape_string($mysqli, $user->getUserIdentifier()).'\' AND (`Password`=\''.\mysqli_real_escape_string($mysqli, $user->getPassword()).'\' OR `Password` = \''.\md5($user->getPassword()).'\') AND (Locked IS NULL OR Locked=0 OR Locked>=2) AND (AllowedIP IS NULL OR LOCATE(\''.$_SERVER['REMOTE_ADDR'].'\',AllowedIP)>0) LIMIT 1'))) {
        if ($result->num_rows) {
            while ($row = $result->fetch_assoc()) {
                $userid = $row['Id'];
                $siteId = $row['SiteId'];
                $accessLevel = $row['AccessLevel'];
            }
        }
        \mysqli_free_result($result);
    }
    if ($userid/* && ( $accessLevel == -1 || $sites[getenv('HTTP_HOST')]['id'] == $siteId ) */) {
        \session_start();
        $_SESSION['userid'] = $userid;

        return $userid;
    } else {
        return false;
    }
}

function get_client_id($mysqli)
{
    $userid = get_user_id($mysqli);
    $clientid = 0;
    if ($userid && ($result = \mysqli_query($mysqli, 'SELECT ClientId FROM SystemUsers WHERE ClientId IS NOT NULL AND id='.$userid))) {
        if ($result->num_rows && ($row = $result->fetch_assoc())) {
            $clientid = $row['ClientId'];
        }
        \mysqli_free_result($result);
    }

    return $clientid;
}

function get_user_level($mysqli)
{
    $userid = get_user_id($mysqli);
    $accesslevel = 0;
    if ($userid && ($result = \mysqli_query($mysqli, 'SELECT AccessLevel FROM SystemUsers WHERE id='.$userid))) {
        if ($result->num_rows && ($row = $result->fetch_assoc())) {
            $accesslevel = $row['AccessLevel'];
        }
        \mysqli_free_result($result);
    }

    return $accesslevel;
}

function get_user_area($mysqli, $field = 'AccessArea')
{
    $userid = get_user_id($mysqli);
    $accessarea = 0;
    if ($userid && ($result = \mysqli_query($mysqli, 'SELECT IFNULL('.$field.',AccessArea) Area FROM SystemUsers WHERE id='.$userid))) {
        if ($result->num_rows && ($row = $result->fetch_assoc())) {
            $accessarea = $row['Area'];
        }
        \mysqli_free_result($result);
    }

    return $accessarea;
}

function get_user_access($mysqli)
{
    $userid = get_user_id($mysqli);
    $access = [];
    if ($userid && ($result = \mysqli_query($mysqli, 'SELECT a.* FROM Access a,SystemUsers u WHERE a.Level=u.AccessLevel AND u.id='.$userid))) {
        if ($row = $result->fetch_assoc()) {
            $access = $row;
        }
        \mysqli_free_result($result);
    }

    return $access;
}

function get_user_sources($mysqli)
{
    $userid = get_user_id($mysqli);
    $sources = [];
    if ($userid && ($result = \mysqli_query($mysqli, 'SELECT a.source_name FROM AccessSource a,SystemUsers u WHERE a.allowed=1 AND a.Level=u.AccessLevel AND u.id='.$userid))) {
        while ($row = $result->fetch_assoc()) {
            $sources[$row['source_name']] = true;
        }
        \mysqli_free_result($result);
    }

    return $sources;
}

function get_user_rules($mysqli)
{
    $userid = get_user_id($mysqli);
    $rules = [];
    if ($userid && ($result = \mysqli_query($mysqli, 'SELECT a.rule_name FROM AccessRule a,SystemUsers u WHERE a.allowed=1 AND a.Level=u.AccessLevel AND u.id='.$userid))) {
        while ($row = $result->fetch_assoc()) {
            $rules[$row['rule_name']] = true;
        }
        \mysqli_free_result($result);
    }

    return $rules;
}

function get_user_message($mysqli)
{
    $clientid = get_client_id($mysqli);
    $userid = get_user_id($mysqli);
    $text = '';
    if ($clientid && ($result = \mysqli_query($mysqli, 'SELECT m.Text FROM Message m, Client c WHERE m.id=c.MessageId AND c.id='.$clientid))) {
        if ($result->num_rows && ($row = $result->fetch_assoc())) {
            $text = $row['Text'];
        }
        \mysqli_free_result($result);
    }
    if ($userid && ($result = \mysqli_query($mysqli, 'SELECT m.Text FROM Message m, SystemUsers u WHERE m.id=u.MessageId AND u.id='.$userid))) {
        if ($result->num_rows && ($row = $result->fetch_assoc())) {
            $text = $row['Text'];
        }
        \mysqli_free_result($result);
    }

    return $text;
}

function auth_basic(): void
{
    global $servicenames;
    \header('WWW-Authenticate: Basic realm=""');
    \header('HTTP/1.1 401 Unauthorized');
    echo 'Для доступа в панель управления введите действующий логин и пароль.';
    if (isset($servicenames[$_SERVER['HTTP_HOST']])) {
        echo '<br/>Если у вас нет логина и пароля, отправьте заявку c сайта '.$servicenames[$_SERVER['HTTP_HOST']];
    }
    $user->getUserIdentifier() = '';
    $user->getPassword() = '';
    if (isset($_SESSION['PHP_AUTH_USER'])) {
        unset($_SESSION['PHP_AUTH_USER']);
    }
    @\session_destroy();
    return;
}

$mysqli = \mysqli_connect($database['server'], $database['login'], $database['password'], $database['name']);
if ($mysqli) {
    \mysqli_query($mysqli, 'Set character set utf8');
    \mysqli_query($mysqli, "Set names 'utf8'");
} else {
    \header('HTTP/1.1 500 Internal Server Error');
    echo 'Внутренняя ошибка сервиса.';
}
/*
$mysqls = mysqli_connect ($dbstat['server'],$dbstat['login'],$dbstat['password'], $dbstat['name']);
if ($mysqls) {
    mysqli_query($mysqls, "Set character set utf8");
    mysqli_query($mysqls, "Set names 'utf8'");
}
*/
$sites = get_sites($mysqli);
$userid = get_user_id($mysqli);
if (!$userid) {
    auth_basic();
}
//    mysqli_close($db);
