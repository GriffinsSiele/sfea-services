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

      if (!isset($_REQUEST['action']) || !in_array($_REQUEST['action'],array('start','stop','pause','continue'))) {
          echo 'Невозможно выполнить действие';
          exit;
      }
      $action = $_REQUEST['action'];

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

      $queue = file_exists("/opt/bulk/$id/queue.txt")?file_get_contents("/opt/bulk/$id/queue.txt"):0;
      if ($action=='start' && $user_level<0) {
          if (file_exists("/opt/forReq/$queue/limit.txt")) @unlink("/opt/forReq/$queue/limit.txt");
          $files = glob("/opt/bulk/".$id."/*/*/*.inc");
          foreach($files as $file){
              unlink($file);
          }

          file_put_contents('/opt/bulk/'.$id.'/status.txt','0');
          $mysqli->query("UPDATE isphere.Bulk SET status=0,results_note='' WHERE id=".$id);
          $msg = 'Обработка реестра запущена.';
      }
      if ($action=='stop') { // Удаляем запросы из очереди
          if (file_exists("/opt/forReq/$queue/limit.txt")) @unlink("/opt/forReq/$queue/limit.txt");
          $files = glob("/opt/forReq/*/".$id."-*");
          foreach($files as $file){
              unlink($file);
          }
          if (file_exists("/opt/forReq/$queue")) file_put_contents("/opt/forReq/$queue/reload.txt","");

//          unlink('/opt/bulk/'.$id.'/status.txt');
          file_put_contents('/opt/bulk/'.$id.'/status.txt','30'); //Надо выгрузить частичные результаты
          $mysqli->query("UPDATE isphere.Bulk SET status=0,results_note='Обработка прервана пользователем' WHERE id=".$id);
          if (file_exists('/opt/bulk/'.$id.'/the.conf')) file_put_contents('/opt/bulk/'.$id.'/comment.txt', 'Обработка прервана пользователем');
          $msg = 'Обработка реестра прервана, будут выгружены неполные результаты.';
      }
      if ($action=='pause' && $user_level<0) {
          if (file_exists("/opt/forReq/$queue/limit.txt")) @unlink("/opt/forReq/$queue/limit.txt");
          $files = glob("/opt/forReq/*/".$id."-*");
          foreach($files as $file){
              if (file_exists($file)) rename($file,preg_replace("/\.qwe/",".pause",$file));
          }
          if (file_exists("/opt/forReq/$queue")) file_put_contents("/opt/forReq/$queue/reload.txt","");

          file_put_contents('/opt/bulk/'.$id.'/status.txt','90');
//          $mysqli->query("UPDATE isphere.Bulk SET status=0,results_note='Обработка приостановлена' WHERE id=".$id);
          $msg = 'Обработка реестра приостановлена.';
      }
      if ($action=='continue' && $user_level<0) {
          if (file_exists("/opt/forReq/$queue/limit.txt")) @unlink("/opt/forReq/$queue/limit.txt");
          $files = glob("/opt/forReq/*/".$id."-*");
          foreach($files as $file){
              if (file_exists($file)) rename($file,preg_replace("/\.pause/",".qwe",$file));
          }
          if (file_exists("/opt/forReq/$queue")) file_put_contents("/opt/forReq/$queue/reload.txt","");

          file_put_contents('/opt/bulk/'.$id.'/status.txt','20');
//          $mysqli->query("UPDATE isphere.Bulk SET status=0,results_note='' WHERE id=".$id);
          $msg = 'Обработка реестра приостановлена.';
      }

      echo '<html><head>';
      echo '<link rel="stylesheet" type="text/css" href="main.css"/>';
//      echo '<meta http-equiv="Refresh" content="30"/></head>';
      echo '<h1>Реестр номер '.$id.'</h1><hr/><a href="admin.php">К списку реестров</a><br/><br/>';

      echo $msg;
      header("Location: bulkinfo.php?id=".$id);

include('footer.php');
