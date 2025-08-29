<?php

include('config.php');
include('auth.php');
include("xml.php");

$user_access = get_user_access($mysqli);
if (!$user_access['chey']) {
    echo 'У вас нет доступа к этой странице';
    exit;
}

set_time_limit($total_timeout+$http_timeout+20);

?>
<h1>Чей телефон</h1><hr/><a href="admin.php">Назад</a><br/><br/>
<form id="checkform" method="POST">
    <table>
        <tr>
            <td>Номер телефона<span class="req">*</span></td>
            <td>
                <input type="text" name="phone" value="<?=$_REQUEST['phone']?>" required="1" maxlength="20" />
            </td>
        </tr>
        <tr>
            <td>Расширенный запрос</td>
            <td>
                <input type="checkbox" name="extended" checked="<?=$_REQUEST['extended']?>" />
            </td>
        </tr>
        <!--tr>
            <td>
                Имя пользователя<span class="req">*</span>
            </td>
            <td>
                <input type="text" name="userid" value="<?=$_REQUEST['userid']?>" required="1" maxlength="50" />
            </td>
        </tr>
        <tr>
            <td>
                Пароль<span class="req">*</span>
            </td>
            <td>
                <input type="password" name="password" value="<?=$_REQUEST['password']?>" required="1"  maxlength="50" />
            </td>
        </tr-->
        <tr>
            <td>Формат ответа:</td>
            <td>
            <select name="mode">
                <option value="html" selected>HTML</option>
                <option value="xml">XML</option>
                <option value="json">JSON</option>
                <option value="txt">Text</option>
            </select>
            </td>
        </tr>

        <tr>
        <td colspan="2"><input type="submit" value="Найти"></td>
        </tr>
    </table>
</form>

<hr/>

<?php

if(!isset($_REQUEST['mode']))
    exit();

if(!isset($_REQUEST['phone']))
    exit();

$ch = curl_init();

$post = array(
    'phone' => $_REQUEST['phone'],
    'userid' => $_SERVER['PHP_AUTH_USER'],
    'password' => $_SERVER['PHP_AUTH_PW'],
    'mode' => $_REQUEST['mode'],
    'type' => $_REQUEST['extended'] ? 'extended' : '',
);

curl_setopt($ch, CURLOPT_URL, $serviceurl.'chey.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, $total_timeout+15);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

$answer = curl_exec($ch);
curl_close($ch);

print "Ответ: <br/>\n";
if ($_REQUEST['mode']!='html') {
  print '<textarea style="width:100%;height:70%">';
}
print $answer;
if ($_REQUEST['mode']!='html') {
  print '</textarea>';
}
