<?php

include('config.php');
include('auth.php');
include("xml.php");

?>
    <form method="POST">
        Запрос XML:<br/>
        <textarea name="xml" style="width:100%;height:70%">
<?=$_REQUEST['xml']?>
        </textarea>
        Формат ответа:<br/>
        <select name="mode">
            <option value="xml" selected>XML</option>
            <option value="json">JSON</option>
        </select>
        <input type="submit" value="Получить данные">
    </form>

<hr/>

<?php

if(!isset($_REQUEST['mode']) || !isset($_REQUEST['xml']))
    exit();

$mode = $_REQUEST['mode'];
$xml = $_REQUEST['xml'];

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $serviceurl.'decision.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('xml'=>$xml,'mode'=>$mode)));
curl_setopt($ch, CURLOPT_POST, 1);

$answer = curl_exec($ch);
curl_close($ch);

print 'Ответ:<br/><textarea style="width:100%;height:20%">';
print $answer;
print '</textarea>';

?>