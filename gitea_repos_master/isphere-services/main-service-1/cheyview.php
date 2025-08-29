<?php

function mysqli_result($res,$row=0,$col=0){ 
    $numrows = mysqli_num_rows($res); 
    if ($numrows && $row <= ($numrows-1) && $row >=0){
        mysqli_data_seek($res,$row);
        $resrow = (is_numeric($col)) ? mysqli_fetch_row($res) : mysqli_fetch_assoc($res);
        if (isset($resrow[$col])){
            return $resrow[$col];
        }
    }
    return false;
}

include('config.php');
include('auth.php');
include("xml.php");

//     $mysqli = mysqli_connect ($database['server'],$database['login'],$database['password'],$database['name']);

$coda = '';
$userid = get_user_id($mysqli);
if($userid && $userid!=14 && $userid!=24){
    $coda = " AND `user_id`='".$userid."'";
}

$id = ( isset($_REQUEST['id']) && preg_match("/^[1-9]\d+$/",  $_REQUEST['id']) ) ? $_REQUEST['id'] : '';
if( !$id ){
    echo 'Nothing to do';
    exit;
}

$sql = "SELECT response FROM RequestIndex WHERE id='".$id."'".$coda." LIMIT 1";
$res = $mysqli->query($sql);

if (!$res) {
    $result['error'] = $mysql->error;
} else {
    $data = mysqli_result($res);
    $res->close();
    $xml = simplexml_load_string($data);
    $result['requestid'] = strval($xml['id']);
    $result['phone'] = $xml->Request->PhoneReq->phone;
    if (substr($result['phone'],0,3)=='800')
        $result['type'] = 'org';
    $social = array();
    $messenger = array();
    foreach($xml->Source as $source){
        if(($source->Name == 'Facebook') && strval($source->ResultsCount)){
            $social[] = 'facebook';
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name') && !isset($result['name'])){
                    $result['name'] = strval($field->FieldValue);
                    $pos = strpos($result['name'],'(');
                    if($pos) $result['name'] = substr($result['name'],0,$pos);
                }
                if($field->FieldName == 'Photo'){
                    $result['image'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'Profile'){
                    $result['facebook'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'Type' && (!$result['type'])){
                    $result['type'] = strval($field->FieldValue)=='user'?'person':'org';
                }
                if($field->FieldName == 'livingplace'){
                    $result['location'] = strval($field->FieldValue);
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
                if(($field->FieldName == 'Name') && !isset($result['name'])){
                    $result['name'] = strval($field->FieldValue);
                    $pos = strpos($result['name'],'(');
                    if($pos) $result['name'] = substr($result['name'],0,$pos);
                }
                if($field->FieldName == 'Photo'){
                    $result['image'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'Link'){
                    $result['vk'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'livingplace'){
                    $result['location'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'birthdate'){
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
        elseif(($source->Name == 'Beholder') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            if (!in_array('vk',$social)) $social[] = 'vk';
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name') && !isset($result['name'])){
                    $result['name'] = strval($field->FieldValue);
                    $pos = strpos($result['name'],'(');
                    if($pos) $result['name'] = substr($result['name'],0,$pos);
                }
                if($field->FieldName == 'Photo'){
                    $result['image'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'Link'){
                    $result['vk'] = strval($field->FieldValue);
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
                if($field->FieldName == 'Name'){
                    $result['name'] = strval($field->FieldValue);
                }
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
                    $result['location'] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'Metro')  && strval($field->FieldValue)){
                    $result['location'] .= ',ì.'.strval($field->FieldValue);
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
                if(($field->FieldName == 'contact_name') && !isset($result['name'])){
                    $result['name'] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'region') && !isset($result['location'])){
                    $result['location'] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'city') && strval($field->FieldValue)){
                    $result['location'] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'metro')  && strval($field->FieldValue)){
                    $result['location'] .= ',ì.'.strval($field->FieldValue);
                }
                if(($field->FieldName == 'address') && strval($field->FieldValue)){
                    $result['location'] .= ','.strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == '2GIS') && strval($source->ResultsCount)){
            if (!$result['type']) $result['type'] = 'org';
            if ($result['type']=='org')
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'categories') && !isset($result['info'])) {
                    $result['info'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'address') {
                    $result['location'] = strval($field->FieldValue);
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
                if(($field->FieldName == 'name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'categories') && !isset($result['info'])) {
                    $result['info'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'address') {
                    $result['location'] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'url') && !isset($result['url'])) {
                    $result['url'] = strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == 'TC') && strval($source->ResultsCount)){
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'Address') && !isset($result['location'])) {
//                    $result['location'] = strval($field->FieldValue);
                }
                if($field->FieldName == 'Website') {
                    if (!$result['type']) $result['type'] = 'org';
                    if(!isset($result['url']))
                        $result['url'] = strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == 'NumBuster') && strval($source->ResultsCount)){
            $first_name = "";
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'first_name') && !isset($result['name'])) {
                    $first_name = strval($field->FieldValue);
                }
                if(($field->FieldName == 'last_name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue) . " " . $first_name;
                }
            }
	}
        elseif(($source->Name == 'PhoneNumber') && strval($source->ResultsCount)){
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue);
                }
                if(($field->FieldName == 'Address') && !isset($result['location'])) {
                    $result['location'] = strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == 'Sberbank') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'name') && !isset($result['name'])) {
                    $result['name'] = strval($field->FieldValue);
                }
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
//                    if (!isset($result['location']))
//                        $result['location'] = trim(strval($field->FieldValue));
                }
                if($field->FieldName == 'Operator'){
                    $result['s_operator'] = strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == 'Skype') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            $messenger[] = 'skype';
            foreach($source->Record->Field as $field){
                if(($field->FieldName == 'Avatar') && !isset($result['name'])) {
                    $result['image'] = strval($field->FieldValue);
                }
            }
	}
        elseif(($source->Name == 'WhatsApp') && strval($source->ResultsCount)){
            $result['type'] = 'person';
            $messenger[] = 'whatsapp';
            $result['smartphone'] = 1;
            foreach($source->Record->Field as $field){
                if($field->FieldName == 'Image'){
                    $result['image'] = strval($field->FieldValue);
                }
            }
	}
/*
        elseif(($source->Name == 'CheckWA') && strval($source->ResultsCount)){
//            $result['type'] = 'person';
            $messenger[] = 'whatsapp';
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
            $messenger[] = 'telegram';
            $result['smartphone'] = 1;
	}
    }
}
if (sizeof($social))
    $result['social'] = implode($social,',');
if (sizeof($messenger))
    $result['messenger'] = implode($messenger,',');

//if ($result['name'] && !$result['type']) $result['type'] = 'person';

if (isset($_REQUEST['mode']) && $_REQUEST['mode']=='json') {
    header("Content-Type: application/json; charset=utf-8");
    $answer = json_encode($result);
} elseif (isset($_REQUEST['mode']) && $_REQUEST['mode']=='xml') {
    header("Content-Type: text/xml; charset=utf-8");
//    $answer = xml_encode(array('response'=>$result));
    $answer = "<?xml version=\"1.0\" encoding=\"utf-8\"?><response>";
    foreach($result as $var => $val)
        $answer .= "<".$var.">".$val."</".$var.">";
    $answer .= "</response>";
} elseif (isset($_REQUEST['mode']) && $_REQUEST['mode']=='text') {
    header("Content-Type: text/plain; charset=utf-8");
    $answer = "";
    foreach($result as $var => $val)
        $answer .= $var.": ".html_entity_decode($val)."\n";
} else {
    header("Content-Type: text/html; charset=utf-8");
    $answer .= "<table>\n";
    foreach($result as $var => $val)
        $answer .= "<tr><td>$var</td><td>".($var=='image' ? "<img src=\"$val\"/>" : (strpos($val,'http')===false ? "" : "<a href=\"$val\">").$val.(strpos($val,'http')===false ? "": "</a>"))."</td></tr>\n";
    $answer .= "</table>\n";
}
echo $answer;

?>