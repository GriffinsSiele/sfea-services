<?php

$last_name = 'Иванов';
$first_name = 'Иван';
$middle_name = 'Иванович';
$birth_date = '07.07.1970';
$passport_series = '1234';
$passport_number = '123456';
$issue_date = '10.10.2010';
$region_id = 0;
$mobile_phone = '79876543210';
$home_phone = '74951234567';
$work_phone = '74957654321';
$additional_phone = '';
$email = '';

$sources = 'fms,fns,fssp,rossvyaz';
$userid = 'test';
$password = 'P@ssw0rd';
$serviceurl = 'http://www.i-sphere.ru/2.00/';

$xml ="
<Request>
        <UserID>{$userid}</UserID>
        <Password>{$password}</Password>
        <requestId>".time()."</requestId>
        <sources>{$sources}</sources>"
. (!$last_name && !$passport_number ? "" : "
        <PersonReq>
            <first>{$first_name}</first>
            <middle>{$middle_name}</middle>
            <paternal>{$last_name}</paternal>"
. (!$birth_date ? "" : "
            <birthDt>{$birth_date}</birthDt>"
) . (!$passport_number ? "" : "
            <passport_series>{$passport_series}</passport_series>
            <passport_number>{$passport_number}</passport_number>"
) . (!$issue_date ? "" : "
            <issueDate>{$issue_date}</issueDate>"
) . (!$region_id ? "" : "
            <region_id>{$region_id}</region_id>"
) . "
        </PersonReq>"
) . (!$mobile_phone ? "" : "
        <PhoneReq>
            <phone>{$mobile_phone}</phone>
        </PhoneReq>"
) . (!$home_phone ? "" : "
        <PhoneReq>
            <phone>{$home_phone}</phone>
        </PhoneReq>"
) . (!$work_phone ? "" : "
        <PhoneReq>
            <phone>{$work_phone}</phone>
        </PhoneReq>"
) . (!$additional_phone ? "" : "
        <PhoneReq>
            <phone>{$additional_phone}</phone>
        </PhoneReq>"
) . (!$email ? "" : "
        <EmailReq>
            <email>{$email}</email>
        </EmailReq>"
) . "
</Request>";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $serviceurl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
curl_setopt($ch, CURLOPT_POST, 1);

$answer = curl_exec($ch);
curl_close($ch);

header ("Content-Type:text/xml");
print $answer;

?>