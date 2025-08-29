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
      $clientid = get_client_id($mysqli);
      $user_level = get_user_level($mysqli);
      $user_area = get_user_area($mysqli,"ResultsArea");

      if ($user_area<=2) {
          $condition .= " AND (user_id=$userid";
          if ($user_area>=1) {
              $condition .= " OR user_id IN (SELECT id FROM SystemUsers WHERE MasterUserId=$userid)";
              if ($user_area>1) {
                  $condition .= " OR client_id=$clientid";
              }
          }
          $condition .= ")";
      }

      $id = ( isset($_REQUEST['id']) && preg_match("/^[1-9]\d+$/",  $_REQUEST['id']) ) ? $_REQUEST['id'] : '';
      if( !$id ){
          $id = 0;
          $theResult = false;
      }

      if ($id) {
          $sql = "SELECT id FROM RequestNew r WHERE id='".$id."'".$condition." LIMIT 1";
          $result = $mysqli->query($sql);

          if (!$result || !mysqli_result($result, 0)){
              $theResult = false;
          } else {
 
            // Тщимся найти чего надо...   варианты:  1. просто файл (недавнии подии)...   2. файл в архиве (давнии подии)...   ответ из БД - с негодованием исключаем из списка...
            // получаем местоположение файла....

            $numName = str_pad($id, 9, '0', STR_PAD_LEFT);
            $titles = str_split($numName, 3);

            if(file_exists('/opt/xml/'.$titles[0].'/'.$titles[1].'/'.$titles[2].'_res.xml')){
                  $theResult = file_get_contents('/opt/xml/'.$titles[0].'/'.$titles[1].'/'.$titles[2].'_res.xml');
                  if (!$theResult) {
                      usleep(10);
                      $theResult = file_get_contents('/opt/xml/'.$titles[0].'/'.$titles[1].'/'.$titles[2].'_res.xml');
                  }
            }elseif(file_exists('/opt/xml/'.$titles[0].'/'.$titles[1].'.tar.gz')){
                  $theResult = shell_exec('tar xzfO /opt/xml/'.$titles[0].'/'.$titles[1].'.tar.gz '.$titles[2].'_res.xml');
            }
          }
      }
            if (!isset($theResult) || !$theResult) {
                if (isset($_REQUEST['mode']) && $_REQUEST['mode']!='xml' && $_REQUEST['mode']!='json') {
                    echo "Данные запроса $id недоступны";
                    exit;
                } else {
                    $theResult = '<?xml version="1.0" encoding="utf-8"?>';
                    $theResult .= '<Response id="'.$id.'" status="-1" datetime="2020-01-01T00:00:00" result="'.$serviceurl.'showresult.php?id='.$id.'&amp;mode=xml" view="'.$serviceurl.'showresult.php?id='.$id.'">';
                    $theResult .= '</Response>';
//                    http_response_code(404);
                }
            }
            if (isset($_REQUEST['mode']) && $_REQUEST['mode']=='xml') {
                header ("Content-Type:text/xml");
                echo $theResult;
            } elseif (isset($_REQUEST['mode']) && $_REQUEST['mode']=='json') {
                header ("Content-Type:application/json");
                $xml = simplexml_load_string($theResult);
                $xml['result'] = strtr($xml['result'],array('mode=xml'=>'mode=json'));
                $json = json_encode($xml, true);
                echo $json;
            } else {
                $doc = xml_transform(strtr($theResult,array('request>'=>'Request>')), isset($_REQUEST['mode']) && $_REQUEST['mode']=='pdf' ? 'isphere_view_pdf.xslt' : 'isphere_view.xslt');
                if ($doc){
                    $servicename = isset($servicenames[$_SERVER['HTTP_HOST']])?'платформой '.$servicenames[$_SERVER['HTTP_HOST']]:'';
                    $login = $_SERVER['PHP_AUTH_USER'];
                    $html = strtr($doc->saveHTML(),array('___servicename___'=>$servicename,'___login___'=>$login));
                    if (isset($_REQUEST['mode']) && $_REQUEST['mode']=='pdf') { 
                        $descriptorspec = [
                            0 => ['pipe', 'r'], //stdin
                            1 => ['pipe', 'w'], //stdout
                            2 => ['pipe', 'w'], //stderr
                        ];
//--disable-smart-shrinking без этого аргумента, всё становится каких-то не правильных пропорций
//--dpi 96 если принудительно не поставить dpi, то размеры указанные в css в милиметрах на печати будут совсем не такими!
//- последний аргумент это прочерк, чтобы передать html через stdin
                        $i = 0; $pdf = false;
                        while ($i++<=3 && !$pdf) {
                            $process = proc_open("xvfb-run -a timeout 5 wkhtmltopdf --quiet --disable-local-file-access --javascript-delay 1000 --margin-left 20mm --dpi 96 - -", $descriptorspec, $pipes);
                            if (is_resource($process)) {
                                copy('view.css','/tmp/view.css');
//Пишем html в stdin
                                fwrite($pipes[0], $html);
                                fclose($pipes[0]);
//Читаем pdf из stdout
                                $pdf = stream_get_contents($pipes[1]);
                                fclose($pipes[1]);
//Читаем ошибки из stderr
                                $err = stream_get_contents($pipes[2]);
                                fclose($pipes[2]);
                                $exitCode = proc_close($process);

                                $start = strpos($pdf,'%PDF');
                                if ($start!==false) {
                                    $pdf = substr($pdf,$start);
                                } else {
                                    if ($pdf) {
                                        file_put_contents('./logs/pdf/'.$_REQUEST['id'].'_'.time().'.txt',$pdf);
                                    } elseif ($i<5) {
                                        sleep(5);
                                    }
                                    $pdf = false;
                                }
                            }
                        }
                        if ($pdf) {
                            header("Content-Type:application/pdf");
                            header("Content-Disposition:attachment; filename=report_".$_REQUEST['id'].".pdf");
                            echo $pdf;
                        } else {
                            echo 'Ошибка сохранения в pdf';
                            file_put_contents('./logs/pdf/'.$_REQUEST['id'].'_'.time().'.txt',$pdf);
                        }
                    } else {
                        echo $html;
                    }
                }else{
                    echo 'Данные недоступны';
                }
            }
