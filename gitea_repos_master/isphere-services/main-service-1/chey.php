<?php

include('config.php');
include("xml.php");

header('Access-Control-Allow-Origin: https://itkom.amocrm.ru');

if ($_SERVER['REQUEST_METHOD']!='POST' && $_SERVER['REQUEST_METHOD']!='GET') {
    exit();
}

set_time_limit($total_timeout+$http_timeout+15);

$result = array();
$error = false;
$sources = 'rossvyaz,facebook,vk,ok,instagram,announcement,boards,commerce,skype,viber,whatsapp,getcontact,numbuster,emt,truecaller,callapp';
//$sources .= ',yamap,2gis,hh,telegram';

if(!isset($_REQUEST['phone'])) {
    $error = "Указаны не все обязательные параметры (телефон)";
}

if(!isset($_REQUEST['userid']) || !isset($_REQUEST['password'])) {
    $error = "Указаны не все обязательные параметры (логин и пароль)";
}

if(isset($_REQUEST['type']) && ($_REQUEST['type']=='extended')) {
    $sources .= ',infobip';
}

if (!$error) {
    if (substr($_REQUEST['phone'],0,2) == '+7')
        $_REQUEST['phone'] = substr($_REQUEST['phone'],2);
    if ((strlen($_REQUEST['phone'])==11) && ((substr($_REQUEST['phone'],0,1)=='8') || (substr($_REQUEST['phone'],0,1)=='8')))
        $_REQUEST['phone'] = substr($_REQUEST['phone'],1);

    $xml = "<Request>
              <UserIP>{$_SERVER['REMOTE_ADDR']}</UserIP>
              <UserID>{$_REQUEST['userid']}</UserID>
              <Password>{$_REQUEST['password']}</Password>
              <requestId>".time()."</requestId>
              <requestType>chey</requestType>
              <sources>{$sources}</sources>
              <PhoneReq><phone>{$_REQUEST['phone']}</phone></PhoneReq>
            </Request>";

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $serviceurl.'index.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $total_timeout+10);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_POST, 1);

    $data = curl_exec($ch);

    curl_close($ch);
}

if ($error) {
    $result['error'] = $error;
} else {
    $data = substr($data,strpos($data,'<?xml'));
    $xml = simplexml_load_string($data);
    $result['requestid'] = strval($xml['id']);
    $result['phone'] = $_REQUEST['phone'];
    $result['image'] = '';
    if (substr($_REQUEST['phone'],0,3)=='800')
        $result['type'] = 'org';
    $social = array();
    $messenger = array();
    foreach($xml->Source as $source){
        if(($source->Name == 'Facebook') && strval($source->ResultsCount)){
            if (!in_array('facebook',$social)) $social[] = 'facebook';
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name')) {
                    $name = strval($field->FieldValue);
                    $pos = strpos($name,'(');
//                    if($pos) $name = trim(substr($name,0,$pos));
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if(($field->FieldName == 'Name') && !isset($result['name'])){
                    $result['name'] = strval($field->FieldValue);
                    $pos = strpos($result['name'],'(');
                    if($pos) $result['name'] = substr($result['name'],0,$pos);
                }
*/
                if($field->FieldName == 'Photo'){
                    $result['image'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'Profile'){
                    if (!isset($result['facebook']) || array_search(strval($field->FieldValue),$result['facebook'])===false)
                        $result['facebook'][] = strval($field->FieldValue);
                }
                if($field->FieldName == 'Type' && (!$result['type'])){
                    $result['type'] = strval($field->FieldValue)=='user'?'person':'org';
                }
                if($field->FieldName == 'livingplace'){
                    $result['location'][] = strval($field->FieldValue);
                }
                if($field->FieldName == 'birthdate'){
                    $result['birthdate'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'gender'){
                    $result['gender'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'job'){
                    $result['info'] = ($result['info']?$result['info'].'; ':'').
                                      strval($field->FieldValue);
                }
                if($field->FieldName == 'website'){
                    $result['url'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'presence'){
                    if(strval($field->FieldValue)=='mobile')
                        $result['smartphone'] = 1;
                }
            }
	}
        elseif(($source->Name == 'VK') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            if (!in_array('vk',$social)) $social[] = 'vk';
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if(($field->FieldName == 'Name') && !isset($result['name'])){
                    $result['name'] = strval($field->FieldValue);
                    $pos = strpos($result['name'],'(');
                    if($pos) $result['name'] = substr($result['name'],0,$pos);
                }
*/
                if($field->FieldName == 'Photo'){
                    $result['image'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'Link'){
                    if (!isset($result['vk']) || array_search(strval($field->FieldValue),$result['vk'])===false)
                        $result['vk'][] = strval($field->FieldValue);
                }
                if($field->FieldName == 'livingplace'){
                    $result['location'][] = strval($field->FieldValue);
                }
                if($field->FieldName == 'birthday'){
                    $result['birthdate'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'job'){
                    $result['info'] = ($result['info']?$result['info'].'; ':'').
                                      strval($field->FieldValue);
                }
                if($field->FieldName == 'website'){
                    $result['url'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'presence'){
                    if(strval($field->FieldValue)=='mobile')
                        $result['smartphone'] = 1;
                }
            }
	}
        elseif(($source->Name == 'OK') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            if (!in_array('ok',$social)) $social[] = 'ok';
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name')) {
                    $name = strval($field->FieldValue);
                    $pos = strpos($name,'**');
//                    if($pos) $name = trim(substr($name,0,$pos));
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if(($field->FieldName == 'Name') && !isset($result['name'])){
                    $result['name'] = strval($field->FieldValue);
                    $pos = strpos($result['name'],'(');
                    if($pos) $result['name'] = substr($result['name'],0,$pos);
                }
*/
            }
	}
        elseif(($source->Name == 'Instagram') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            if (!in_array('instagram',$social)) $social[] = 'instagram';
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
                if($field->FieldName == 'Image'){
                    $result['image'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'Link'){
                    if (!isset($result['instagram']) || array_search(strval($field->FieldValue),$result['instagram'])===false)
                        $result['instagram'][] = strval($field->FieldValue);
                }
                if($field->FieldName == 'Website'){
                    $result['url'] = strval($field->FieldValue);
                }
            }
            $result['smartphone'] = 1;
	}
        elseif(($source->Name == 'Beholder') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            if (!in_array('vk',$social)) $social[] = 'vk';
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if(($field->FieldName == 'Name') && !isset($result['name'])){
                    $result['name'] = strval($field->FieldValue);
                    $pos = strpos($result['name'],'(');
                    if($pos) $result['name'] = substr($result['name'],0,$pos);
                }
*/
                if($field->FieldName == 'Photo'){
//                    $result['image'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'Link'){
//                    $result['vk'][] = strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == 'VK-Phone') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            if (!in_array('vk',$social)) $social[] = 'vk';
        }
        elseif(($source->Name == 'HH') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if($field->FieldName == 'Name'){
                    $result['name'] = strval($field->FieldValue);
                }
*/
                if($field->FieldName == 'Photo'){
                    $result['image'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'Age'){
                    $result['age'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'BirthDate'){
                    $result['birthdate'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'Gender'){
                    $result['gender'] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'City') && strval($field->FieldValue)){
                    $result['location'][] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'Metro') && strval($field->FieldValue)){
                    $result['location'][sizeof($result['location'])-1] .= ', м.'.strval($field->FieldValue);
                }
                if($field->FieldName == 'Occupation'){
                    $result['info'] = (isset($result['info'])?$result['info'].'; ':'').
                                      strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == 'Announcement') && strval($source->ResultsCount)){
//            $result['type'] = 'person';
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'contact_name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if(($field->FieldName == 'contact_name') && !isset($result['name'])){
                    $result['name'] = strval($field->FieldValue);
                }
*/
                if(($field->FieldName == 'region')/* && !isset($result['location'])*/){
                    $result['location'][] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'city') && strval($field->FieldValue)){
                    $result['location'][sizeof($result['location'])-1] .= ','.strval($field->FieldValue);
                }
                if(($field->FieldName == 'metro')  && strval($field->FieldValue)){
                    $result['location'][sizeof($result['location'])-1] .= ',м.'.strval($field->FieldValue);
                }
                if(($field->FieldName == 'address') && strval($field->FieldValue)){
                    $result['location'][sizeof($result['location'])-1] .= ','.strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == 'boards') && strval($source->ResultsCount)){
//            $result['type'] = 'person';
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
                if(($field->FieldName == 'Location') && strval($field->FieldValue)){
                    $result['location'][] = strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == '2GIS') && strval($source->ResultsCount)){
            if (!$result['type']) $result['type'] = 'org';
            if ($result['type']=='org')
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if(($field->FieldName == 'name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue);
                }
*/
                if(($field->FieldName == 'categories') && !isset($result['info'])) {
                    $result['info'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'address') {
                    $result['location'][] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'website') && !isset($result['url'])) {
                    $result['url'] = strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == 'YaMap') && strval($source->ResultsCount)){
            if (!$result['type']) $result['type'] = 'org';
            if ($result['type']=='org')
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if(($field->FieldName == 'name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue);
                }
*/
                if(($field->FieldName == 'categories') && !isset($result['info'])) {
                    $result['info'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'address') {
                    $result['location'][] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'url') && !isset($result['url'])) {
                    $result['url'] = strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == 'TrueCaller') && strval($source->ResultsCount)){
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if(($field->FieldName == 'Name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue);
                }
*/
                if(($field->FieldName == 'Address')/* && !isset($result['location'])*/) {
//                    $result['location'][] = strval($field->FieldValue);
                }
                if($field->FieldName == 'Website') {
                    if (!$result['type']) $result['type'] = 'org';
                    if(!isset($result['url']))
                        $result['url'] = strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == 'emt') && strval($source->ResultsCount)){
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if(($field->FieldName == 'Name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue);
                }
*/
            }
	}
        elseif(($source->Name == 'NumBuster') && strval($source->ResultsCount)){
            $first_name = "";
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'first_name')) {
                    $first_name = strval($field->FieldValue);
                }
                if(($field->FieldName == 'last_name')) {
                    $name = trim($first_name . " " . strval($field->FieldValue));
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if(($field->FieldName == 'last_name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue) . " " . $first_name;
                }
*/
            }
	}
        elseif(($source->Name == 'GetContact') && strval($source->ResultsCount)){
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if(($field->FieldName == 'Name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue);
                }
*/
            }
	}
        elseif(($source->Name == 'PhoneNumber') && strval($source->ResultsCount)){
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if(($field->FieldName == 'Name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue);
                }
*/
                if(($field->FieldName == 'Address')/* && !isset($result['location'])*/) {
                    $result['location'][] = strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == 'Sberbank') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if(($field->FieldName == 'name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue);
                }
*/
            }
	}
        elseif(($source->Name == 'Tinkoff') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name')) {
                    $name = strval($field->FieldValue);
                    $pos = strpos($name,'**');
//                    if($pos) $name = trim(substr($name,0,$pos));
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if(($field->FieldName == 'name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue);
                }
*/
            }
	}
        elseif($source->Name == 'Rossvyaz'){
            foreach($source->Record->Field as $field){
                if($field->FieldName == 'PhoneOperator'){
                    $result['operator'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'PhoneStandart'){
                    $result['standart'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'PhoneRegion'){
                    $result['region'] = trim(strval($field->FieldValue));
                }
                if($field->FieldName == 'Operator'){
                    $result['s_operator'] = strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == 'Skype') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            if (!in_array('skype',$messenger)) $messenger[] = 'skype';
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
/*
                if(($field->FieldName == 'Name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue);
                }
*/
                if($field->FieldName == 'Login'){
                    if (!isset($result['skype']) || array_search(strval($field->FieldValue),$result['skype'])===false)
                        $result['skype'][] = strval($field->FieldValue);
                }
                if($field->FieldName == 'Birthday'){
                    $result['birthdate'] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'City') && strval($field->FieldValue)){
                    $result['location'][] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'Avatar') && !$result['image']) {
                    $result['image'] = strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == 'WhatsApp') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            if (!in_array('whatsapp',$messenger)) $messenger[] = 'whatsapp';
            $result['smartphone'] = 1;
            foreach($source->Record->Field as $field){
                if($field->FieldName == 'Photo'){
                    $result['image'] = strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == 'Viber') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            if (!in_array('viber',$messenger)) $messenger[] = 'viber';
            $result['smartphone'] = 1;
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
                if($field->FieldName == 'Photo'){
                    $result['image'] = strval($field->FieldValue);
                }
            }
	}
/*
        elseif(($source->Name == 'CheckWA') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            if (!in_array('whatsapp',$messenger)) $messenger[] = 'whatsapp';
            $result['smartphone'] = 1;
            foreach($source->Record->Field as $field){
                if($field->FieldName == 'Image'){
                    $result['image'] = strval($field->FieldValue);
                }
            }
	}
*/
        elseif(($source->Name == 'Telegram') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            if (!in_array('telegram',$messenger)) $messenger[] = 'telegram';
            $result['smartphone'] = 1;
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name')) {
                    $name = strval($field->FieldValue);
                    if (isset($result['name'])) {
                        if (array_search($name,$result['name'])===false)
                            $result['name'][] = $name;
                    } else {
                        $result['name'][] = $name;
                    }
                }
                if($field->FieldName == 'Photo'){
                    $result['image'] = strval($field->FieldValue);
                }
            }
	}
    }
}
if (isset($result['name']))
    $result['name'] = implode($result['name'],'; ');
if (isset($result['location']))
    $result['location'] = implode($result['location'],"; ");
if (sizeof($social))
    $result['social'] = implode($social,',');
if (sizeof($messenger))
    $result['messenger'] = implode($messenger,',');
if (isset($result['facebook']))
    $result['facebook'] = implode($result['facebook'],"; ");
if (isset($result['vk']))
    $result['vk'] = implode($result['vk'],"; ");
if (isset($result['instagram']))
    $result['instagram'] = implode($result['instagram'],"; ");
if (isset($result['skype']))
    $result['skype'] = implode($result['skype'],"; ");

//if ($result['name'] && !$result['type']) $result['type'] = 'person';

if ($_REQUEST['mode']=='json') {
    header("Content-Type: application/json; charset=utf-8");
    $answer = json_encode($result);
} elseif ($_REQUEST['mode']=='xml') {
    header("Content-Type: text/xml; charset=utf-8");
//    $answer = xml_encode(array('response'=>$result));
    $answer = "<?xml version=\"1.0\" encoding=\"utf-8\"?><response>";
    foreach($result as $var => $val)
        $answer .= "<".$var.">".$val."</".$var.">";
    $answer .= "</response>";
} elseif ($_REQUEST['mode']=='html') {
    header("Content-Type: text/html; charset=utf-8");
    $answer .= "<table>\n";
    foreach($result as $var => $val)
        $answer .= "<tr><td>$var</td><td>".($var=='image' ? "<img src=\"$val\"/>" : (strpos($val,'http')===false ? "" : "<a href=\"$val\">").$val.(strpos($val,'http')===false ? "": "</a>"))."</td></tr>\n";
    $answer .= "</table>\n";
} else {
    header("Content-Type: text/plain; charset=utf-8");
    $answer = "";
    foreach($result as $var => $val)
        $answer .= $var.": ".html_entity_decode($val)."\n";
}
echo $answer;
