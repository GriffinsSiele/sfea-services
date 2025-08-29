<?php

include('config.php');
include('auth.php');
include("xml.php");

set_time_limit($total_timeout+$http_timeout+15);

$user_level = get_user_level($mysqli);

if (!isset($_REQUEST['xml'])) $_REQUEST['xml']='';

?>
<h1>Запрос XML</h1><hr/><a href="admin.php">Назад</a><br/><br/>
<form id="checkform" method="POST">
    <table>
        <tr>
            <td>XML</td>
            <td>
                <textarea name="xml" cols="100" rows="20">
<?=$_REQUEST['xml']?>
                </textarea>
            </td>
        </tr>
        <tr>
            <td>Формат ответа:</td>
            <td>
            <select name="mode">
                <option value="xml">XML</option>
                <option value="html" selected>HTML</option>
            </select>
            </td>
        </tr>

        <tr>
        <td colspan="2"><input type="submit" value="Получить данные"></td>
        </tr>
    </table>
</form>

<hr/>

<?php

if(!isset($_REQUEST['xml']))
    exit();

$xml = $_REQUEST['xml'];

//if ($_REQUEST['mode']=='xml') {
    print 'Запрос XML: <textarea style="width:100%;height:30%">';
    print $xml;
    print '</textarea>';
    print "<hr/>";
//}

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $serviceurl.'index.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, $total_timeout+10);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
curl_setopt($ch, CURLOPT_POST, 1);

$answer = curl_exec($ch);
curl_close($ch);

if ($_REQUEST['mode']=='xml') {
    print 'Ответ XML: <textarea style="width:100%;height:70%">';
    print $answer;
    print '</textarea>';
} else {
    $answer = substr($answer,strpos($answer,'<?xml'));
    $doc = xml_transform($answer, 'isphere_view.xslt');
    if ($doc) {
        $servicename = isset($servicenames[$_SERVER['HTTP_HOST']])?'платформой '.$servicenames[$_SERVER['HTTP_HOST']]:'';
        echo strtr($doc->saveHTML(),array('$servicename'=>$servicename));
    } else  {
        echo $answer?'Некорректный ответ сервиса':'Нет ответа от сервиса';
    }
}
