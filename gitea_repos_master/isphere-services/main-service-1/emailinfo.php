<?php

include('config.php');
include("xml.php");

$error = false;

if(!isset($_REQUEST['email'])) {
    $error = "Указаны не все обязательные параметры (e-mail)";
}

if(!isset($_REQUEST['userid']) || !isset($_REQUEST['password'])) {
    $error = "Указаны не все обязательные параметры (логин и пароль)";
}

if (!$error) {
    $xml = "<Request>
              <UserID>{$_REQUEST['userid']}</UserID>
              <Password>{$_REQUEST['password']}</Password>
              <requestId>".time()."</requestId>
              <sources>smtp,facebook,hh,announcement</sources>
              <EmailReq><email>{$_REQUEST['email']}</email></EmailReq>
            </Request>";

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $serviceurl.'index.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_POST, 1);

    $data = curl_exec($ch);

    $answer = $data;

    curl_close($ch);

}

if ($error) {
    $answer = "<?xml version=\"1.0\" encoding=\"utf-8\"?><Error>$error</Error>";
}

if ($_REQUEST['mode']=='xml') {
    header("Content-Type: text/xml; charset=utf-8");
    echo $answer;
} else {
    $doc = xml_transform($answer, 'isphere_view.xslt');
    if ($doc) {
        $servicename = isset($servicenames[$_SERVER['HTTP_HOST']])?'платформой '.$servicenames[$_SERVER['HTTP_HOST']]:'';
        echo strtr($doc->saveHTML(),array('$servicename'=>$servicename));
    } else  {
        echo $answer?'Некорректный ответ сервиса':'Нет ответа от сервиса';
    }
}
