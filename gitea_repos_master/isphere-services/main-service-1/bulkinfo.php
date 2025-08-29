<?php

      include ('config.php');
      include ('auth.php');

      $condition = '';
      $userid = get_user_id($mysqli);
      $user_level = get_user_level($mysqli);
      $user_area = get_user_area($mysqli);
      $user_access = get_user_access($mysqli);

      if (!$user_access['bulk']) {
          echo 'У вас нет доступа к этой странице';
          exit;
      }

      if ($user_area<=2) {
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
          $id = 0;
      }

      $select = "SELECT * FROM isphere.Bulk WHERE id=$id $condition LIMIT 1";

      $sqlRes = $mysqli->query($select);
      if ($sqlRes->num_rows>0) {
          $result = $sqlRes->fetch_assoc();
      }else{
          echo "Данные реестра $id недоступны";
          exit;
      }
      $sqlRes->close();

      $titles = array(0=>'Добавлен в очередь', 10=>'Проверка исходных данных', 20=>'Выполнение запросов', 30=>'Проверка результатов', 40=>'Выгрузка результатов', 90=>'Приостановлен');

      echo '<html><head>';
      echo '<link rel="stylesheet" type="text/css" href="main.css"/>';
//      echo '<meta http-equiv="Refresh" content="30"/></head>';
      echo '<h1>'.$result['filename'].'</h1><hr/><a href="bulk.php">К списку реестров</a><br/><br/>';
//      echo '<a href="/2.00/bulkAuto/stepOne.php" target=_blank>Загрузить новый реестр</a><br /><br />';
//      echo '<pre>';
//      print_r($result);
//      echo '</pre>';
      if(file_exists('/opt/bulk/'.$id.'/status.txt')){
          $status = file_get_contents('/opt/bulk/'.$id.'/status.txt');

          echo '<b>Реестр '.$result['filename'].' ('.$result['total_rows'].' строк) отправлен на обработку по источникам '.strtr($result['sources'],array(','=>', ')).'</b><br />';
          echo  'Статус: '.$titles[$status].'<br />Выполняется обработка.<br />';
          echo '<a href="bulkaction.php?action=stop&id='.$id.'" onclick="return confirm(\'Обработка реестра не завершена, будут выгружены неполные результаты. Вы уверены, что хотите прервать обработку?\')">Прервать обработку</a><br />';
      }elseif($result['status']==1){
          echo '<b>Реестр '.$result['filename'].' ('.$result['total_rows'].' строк) обработан</b><br />';
          echo '<a href="bulkresult.php?id='.$id.'">Скачать результаты</a><br /><br />';
      }elseif($result['status']==2){
          echo '<b>Реестр '.$result['filename'].' ('.$result['total_rows'].' строк) ожидает обработки по источникам '.$result['sources'].'</b><br />';
          echo 'Реестр в ожидании обработки<br /><br />';
      }elseif($result['status']==3){
          echo 'Обработка реестра '.$result['filename'].' прервана<br /><br />';
      }
      if ($user_level<0 && $result['status']>0) {
          echo '<a href="bulkaction.php?action=start&id='.$id.'" onclick="return confirm(\'Запустить обработку реестра?\')">Запустить обработку</a><br />';
      }

      if(!isset($status) || $status>10){
          if ($user_level<0 && isset($status) && $status==20) {
              echo '<a href="bulkaction.php?action=pause&id='.$id.'" onclick="return confirm(\'Приостановить обработку реестра?\')">Приостановить обработку</a><br />';
          }
          if ($user_level<0 && isset($status) && $status==90) {
              echo '<a href="bulkaction.php?action=continue&id='.$id.'" onclick="return confirm(\'Возобновить обработку реестра?\')">Возобновить обработку</a><br />';
          }

          $dirs = glob('/opt/bulk/'.$id.'/*',GLOB_ONLYDIR);
//          $sources = explode(',', $result['sources']);
//          foreach($sources as $source){
          foreach($dirs as $dir){
              preg_match("/\/([a-z0-9_]+)$/", $dir, $matches);
              $source = $matches[1];
              echo '<br />Источник: '.$source.'<br />';
              $files = glob('/opt/bulk/'.$id.'/'.$source."/*/fResult.txt");
              echo 'Обработано: '.count($files).'<br />';
              $errors = '';
              foreach($files as $file){
                  $fileContent = file_get_contents($file);
                  if(preg_match("/<Error>([^<]+)<\/Error>/", $fileContent, $matches) || preg_match("/<message>([^<]+)<\/message>/", $fileContent, $matches)){
                        $tmp = explode('/', $file);
                        $incFile = strtr($file,array('.txt'=>'.inc'));
                        $inc = file_exists($incFile)?file_get_contents($incFile):0;
                        $errors .= 'Строка '.$tmp[5].': '.$matches[0].($inc?' ('.$inc.' попыток)':'').'<br />';
                  }
              }
              if($errors != ''){
                   echo 'Ошибки:<br />';
                   echo $errors;
              }
           }
      }

include('footer.php');
