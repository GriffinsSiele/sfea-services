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
//      include('auth.php');
      include("xml.php");

      $mysqli = mysqli_connect($database['server'],$database['login'],$database['password'],$database['name']);

      $condition = '';
/*
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
*/
      $id = ( isset($_REQUEST['id']) && preg_match("/^[1-9]\d+$/",  $_REQUEST['id']) ) ? $_REQUEST['id'] : 0;
      $name = ( isset($_REQUEST['name']) && preg_match("/^[a-zA-Z0-9_-]+\.[a-zA-Z]+$/", $_REQUEST['name']) ) ? $_REQUEST['name'] : false;
      $theResult = false;

      if ($id && $name) {
          $sql = "SELECT id FROM RequestNew r WHERE id='".$id."'".$condition." LIMIT 1";
          $result = $mysqli->query($sql);
          $filename = './logs/files/'.$id.'_'.$name;

          if ($result && mysqli_result($result, 0) && resource_exists($filename)){
              $theResult = resource_get_contents($filename);
          }
      }

      if ($theResult) {
          header('Content-Type: application/octet-stream');
          header('Content-Disposition: attachment; filename='.$name);
          echo $theResult;
      } else {
          header('HTTP/1.1 404 Not Found'); 
          echo 'Файл '.$_REQUEST['name'].' недоступен';
      }
