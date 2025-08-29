<?php

$sources = 'fns,vestnik';
$userid = 'admin';
$password = 'MisterX2011';
$serviceurl = 'https://www.i-sphere.ru/2.00/';
$inn = '7219010450';

$xml ="
<Request>
    <UserID>{$userid}</UserID>
    <Password>{$password}</Password>
    <requestId>".time()."</requestId>
    <sources>{$sources}</sources>
    <OrgReq>
    	<inn>{$inn}</inn>
    </OrgReq>
</Request>";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $serviceurl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 180);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
curl_setopt($ch, CURLOPT_POST, 1);

$answer = curl_exec($ch);
if($answer === false)
{
    echo 'Ошибка curl: ' . curl_error($ch);
}
else
{
    echo 'Операция завершена без ошибок';
}

file_put_contents('./logs/isphere_sample.xml',$answer);

// header ("Content-Type:text/xml");
// print $answer;

?>
