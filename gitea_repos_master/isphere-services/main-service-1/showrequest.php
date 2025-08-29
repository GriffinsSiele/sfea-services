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

//      $mysqli = mysqli_connect ($database['server'],$database['login'],$database['password'],$database['name']);

      $condition = '';
      $userid = get_user_id($mysqli);
      $user_level = get_user_level($mysqli);
      $user_area = get_user_area($mysqli);

      if ($user_area<=2) {
          echo 'У вас нет доступа к этой странице';
          exit;

          $condition .= " AND (user_id=$userid";
          if ($user_area>=1) {
              $condition .= " OR user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId=$userid)";
              if ($user_area>1) {
                  $condition .= " OR user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId IN (SELECT id FROM SystemUsers WHERE MasterUserId=$userid))";
              }
          }
          $condition .= ")";
      }

      $id = ( isset($_REQUEST['id']) && preg_match("/^[1-9]\d+$/",  $_REQUEST['id']) ) ? $_REQUEST['id'] : '';
      if( !$id ){
               echo 'No valid id';
               exit;
      }

            // Тщимся найти чего надо...   варианты:  1. просто файл (недавнии подии)...   2. файл в архиве (давнии подии)...   ответ из БД - с негодованием исключаем из списка...
            // получаем местоположение файла....

            $numName = str_pad($id, 9, '0', STR_PAD_LEFT);
            $titles = str_split($numName, 3);

            if(file_exists('/opt/xml/'.$titles[0].'/'.$titles[1].'/'.$titles[2].'_req.xml')){
                  $theResult = file_get_contents('/opt/xml/'.$titles[0].'/'.$titles[1].'/'.$titles[2].'_req.xml');
/*
                  if(preg_match_all("/<FieldValue>(https\:\/\/(www.)?i\-sphere.ru\/2\.00[^\<]+)/si", $theResult, $matches)){
                          foreach($matches[1] as $key => $link){       //   тут можно генерить временную ссылку и повсякому изголяться...
                                 if(file_exists('/opt/xml/'.$titles[0].'/'.$titles[1].'/'.basename($link))){
                                       copy('/opt/xml/'.$titles[0].'/'.$titles[1].'/'.basename($link), '/var/www/i-sphere.ru/theTmp/'.basename($link));
                                 }else{
                                         echo '/opt/xml/'.$titles[0].'/'.$titles[1].'/'.basename($link);
                                         exit;
                                 }
                          }
                          $theResult = preg_replace("/<FieldValue>https\:\/\/(www.)?i\-sphere.ru\/2\.00[^\<]+\//si", '<FieldValue>https://i-sphere.ru/theTmp/', $theResult);
                  }
*/
            }elseif(file_exists('/opt/xml/'.$titles[0].'/'.$titles[1].'.tar.gz')){
//                  echo 'here<br />';
//                  echo 'tar xzfO /opt/xmlTest/'.$titles[0].'/'.$titles[1].'.tar.gz';
                  $theResult = shell_exec('tar xzfO /opt/xml/'.$titles[0].'/'.$titles[1].'.tar.gz '.$titles[2].'_req.xml');
/*
                  if(preg_match_all("/<FieldValue>(https\:\/\/(www.)?i\-sphere.ru\/2\.00[^\<]+)/si", $theResult, $matches)){
                          foreach($matches[1] as $key => $link){       //   тут можно генерить временную ссылку и повсякому изголяться...
//                                 if(file_exists('/opt/xml/'.$titles[0].'/'.$titles[1].'/'.basename($link))){
                                 if(shell_exec('tar -tf /opt/xml/'.$titles[0].'/'.$titles[1].'.tar.gz | grep '.basename($link))){
//                                       copy('/opt/xml/'.$titles[0].'/'.$titles[1].'/'.basename($link), '/var/www/i-sphere.ru/theTmp/'.basename($link));
                                         shell_exec('tar xzfO /opt/xml/'.$titles[0].'/'.$titles[1].'.tar.gz '.basename($link).' > /var/www/i-sphere.ru/theTmp/'.basename($link));
                                 }else{
                                         echo '/opt/xml/'.$titles[0].'/'.$titles[1].'/'.basename($link);
                                         exit;
                                 }
                          }
                          $theResult = preg_replace("/<FieldValue>https\:\/\/(www.)?i\-sphere.ru\/2\.00[^\<]+\//si", '<FieldValue>https://i-sphere.ru/theTmp/', $theResult);
                  }
*/
            }
            if ($theResult) {
                $theResult = preg_replace("/<\?xml[^>]+>/", "", strtr(substr($theResult,strpos($theResult,'<')),array('<request'=>'<Request','</request'=>'</Request')));
            }
            if (!$theResult) {
                if (!isset($_REQUEST['mode']) || (isset($_REQUEST['mode']) && $_REQUEST['mode']!='xml' && $_REQUEST['mode']!='json')) {
                    echo "Данные запроса $id недоступны";
                    exit;
                } else {
                    $theResult = '<?xml version="1.0" encoding="utf-8"?>';
                    $theResult .= '<Request></Request>';
//                    http_response_code(404);
                }
            } elseif (isset($_REQUEST['mode']) && $_REQUEST['mode']=='xml') {
                header ("Content-Type:text/xml");
                echo $theResult;
            } else {
                $doc = xml_transform('<Response>'.$theResult.'</Response>', 'isphere_view.xslt');
                if ($doc){
                    $servicename = isset($servicenames[$_SERVER['HTTP_HOST']])?'платформой '.$servicenames[$_SERVER['HTTP_HOST']]:'';
                    $login = $_SERVER['PHP_AUTH_USER'];
                    $html = strtr($doc->saveHTML(),array('___servicename___'=>$servicename,'___login___'=>$login));
                    echo $html;
                }else{
                    echo 'Данные недоступны';
                }
            }
