<?php

require('config.php');
require('auth.php');
require('user/functions.php');

$user_access = get_user_access($mysqli);
if (!$user_access['users']) {
    echo 'У вас нет доступа к этой странице';
    exit;
}

$user_level = get_user_level($mysqli);
$user_access = get_user_access($mysqli);
$user_area = get_user_area($mysqli);

$action = (isset($_REQUEST['action']) && in_array($_REQUEST['action'], array('checkLogin', 'checkEmail', 'createUser', 'updateUserField'))) ? $_REQUEST['action'] : '';


if($action == 'updateUserField'){
      $id = ( isset($_REQUEST['id']) && preg_match("/^ch_(\d+$)/", $_REQUEST['id'], $matches)) ? $matches[1] : '';
      $aKey = ( isset($_REQUEST['aKey']) && in_array($_REQUEST['aKey'], array('AccessLevel', 'AccessArea', 'Email', 'Phone', 'Name', 'Locked', 'Deleted', 'SiteId', 'MasterUserId'))) ? $_REQUEST['aKey'] : '';
      $newValue = trim($_REQUEST['newValue']);

      if(!checkPermission($mysqli, $action, $user_level, $user_access, $userid, $id, $aKey)){
           $response['error'] = 1;
           $response['text'] = "У Вас нет прав для редактирования этого поля!";
           emergExit($response);
      }

      if($result = mysqli_query($mysqli, "SELECT ".$aKey." FROM SystemUsers WHERE Id='".$id."' LIMIT 1" )){
            if($result->num_rows > 0){
                  if($result->fetch_row()[0] != $newValue){
                        if($aKey == 'Email'){
                             $email = $newValue;
                             if(!preg_match("/^[a-z0-9\._]+\@[a-z]+\.[a-z]{2,4}$/i", $email)){
                                   $response['error'] = 1;
                                   $response['text'] = "Email не соответствует формату";
                                   emergExit($response);
                              }else{
                                   if($result = mysqli_query($mysqli, 'SELECT * FROM SystemUsers WHERE Email=\''.$email.'\' LIMIT 1' )){
                                         if($result->num_rows > 0){
                                               $response['error'] = 1;
                                               $response['text'] = "Email существует!";
                                               emergExit($response);
                                         }
                                    }else{
                                          $response['error'] = 1;
                                          $response['text'] = "Плохое время - чтобы заводить пользователя - возвращайтесь позже!!";
                                          emergExit($response);
                                    }
                              }
                        }elseif($aKey == 'Name' &&  !preg_match("/^[а-яё\- ]+$/ui", $newValue)){
                               $response['error'] = 1;
                               $response['text'] = "Не похоже на Имя";
                               emergExit($response);
                        }elseif($aKey == 'Phone' &&  !preg_match("/^[\+0-9 \-\(\)]+$/", $newValue)){
                               $response['error'] = 1;
                               $response['text'] = "Не похоже на телефон";
                               emergExit($response);
                        }elseif($aKey == 'SiteId'){
                             if($result = mysqli_query($mysqli, "SELECT * FROM Site WHERE id='".$newValue."' LIMIT 1")){
                                  if(!$result->num_rows){
                                      $response['error'] = 1;
                                      $response['text'] = "Нет такого сайта в списке!";
                                      emergExit($response);
                                  }
                             }else{
                                  $response['error'] = 1;
                                  $response['text'] = "Нет такого сайта в списке!";
                                  emergExit($response);
                             }
                        }elseif($aKey == 'MasterUserId'){
                              if($newValue){
                                  if($result = mysqli_query($mysqli, "SELECT `MasterUserId` FROM SystemUsers WHERE Id='".$newValue."' LIMIT 1" )){
                                      if(!$result->num_rows){
                                          $response['error'] = 1;
                                          $response['text'] = "Нет такого мастер-пользователя!";
                                          emergExit($response);
                                      }
                                  }else{
                                      $response['error'] = 1;
                                      $response['text'] = "Нет такого мастер-пользователя!";
                                      emergExit($response);
                                  }
                             }
                        }elseif($aKey == 'Locked' || $aKey == 'Deleted'){
                            $newValue = intval($newValue);
                            if($newValue > 1){
                                 $newValue = 1;
                            }
                        }elseif($aKey == 'AccessLevel'){
                             if($newValue < 0){
                                  $newValue = 0;
                             }
                        }elseif($aKey == 'AccessLevel'){
                             if($newValue > 1){
                                  $newValue = 1;
                             }
                        }else{
                        }

                       // Наконец!!
                       if(!$newValue && in_array($aKey, array('MasterUserId', 'Locked'))){
                             mysqli_query($mysqli, "UPDATE SystemUsers SET ".$aKey."=0 WHERE Id='".$id."'");
                       }else{
                             mysqli_query($mysqli, "UPDATE SystemUsers SET ".$aKey."='".$newValue."' WHERE Id='".$id."'");
                       }
                       if($mysqli->affected_rows){
                              $response['text'] = "Удача!";
                              emergExit($response);
                       }else{
                              $response['error'] = 1;
                              $response['text'] = "Неудача!";
                              emergExit($response);
                       }

                  }else{
                      $response['error'] = 1;
                      $response['text'] = "Нечего менять!!";
                      emergExit($response);
                  }
            }else{
               $response['error'] = 1;
               $response['text'] = "Нечего менять!!";
               emergExit($response);
            }
      }else{
               $response['error'] = 1;
               $response['text'] = "Плохое время - чтобы менять значение поля - возвращайтесь позже!! SELECT ".$aKey." FROM SystemUsers WHERE Id='".$id."' LIMIT 1";
               emergExit($response);
      }
      exit;
}

if($action == 'checkLogin' && $user_access['users'] == 1){
     $login = isset($_REQUEST['login']) ? trim($_REQUEST['login']) : '';
     if(!preg_match("/^[a-z0-9_\-\.]{4,15}$/i", $login)){
          $response['error'] = 1;
          $response['text'] = "Логин от 4 до 15 латинских букв, цифр, символов . _ -";
          emergExit($response);
     }else{
          if($result = mysqli_query($mysqli, 'SELECT * FROM SystemUsers WHERE Login=\''.$login.'\' LIMIT 1' )){
                if($result->num_rows > 0){
                       $response['error'] = 1;
                       $response['text'] = "Логин существует!";
                       emergExit($response);
                }else{
                      $response['text'] = "Очень хороший логин!";
                      emergExit($response);
                }
          }else{
               $response['error'] = 1;
               $response['text'] = "Плохое время - чтобы заводить пользователя - возвращайтесь позже!!";
               emergExit($response);
          }
     }
}


if($action == 'checkEmail' && $user_access['users'] == 1){
     $email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : '';
     if(!preg_match("/^[a-z0-9\._]+\@[a-z]+\.[a-z]{2,4}$/i", $email)){
          $response['error'] = 1;
          $response['text'] = "Email не соответствует формату";
          emergExit($response);
     }else{
          if($result = mysqli_query($mysqli, 'SELECT * FROM SystemUsers WHERE Email=\''.$email.'\' LIMIT 1' )){
                if($result->num_rows > 0){
                       $response['error'] = 1;
                       $response['text'] = "Email существует!";
                       emergExit($response);
                }else{
                      $response['text'] = "Очень хороший Email!";
                      emergExit($response);
                }
          }else{
               $response['error'] = 1;
               $response['text'] = "Плохое время - чтобы заводить пользователя - возвращайтесь позже!!";
               emergExit($response);
          }
     }
}


if($action == 'createUser' && $user_access['users'] == 1){
     $login = isset($_REQUEST['login']) ? trim($_REQUEST['login']) : '';
     if(!preg_match("/^[a-z0-9_\-\.]{4,15}$/i", $login)){
          $response['error'] = 1;
          $response['text'] = "Логин от 4 до 15 латинских букв, цифр, символов . _ -";
          emergExit($response);
     }else{
          if($result = mysqli_query($mysqli, 'SELECT * FROM SystemUsers WHERE Login=\''.$login.'\' LIMIT 1' )){
                if($result->num_rows > 0){
                       $response['error'] = 1;
                       $response['text'] = "Логин существует!";
                       emergExit($response);
                }
          }else{
               $response['error'] = 1;
               $response['text'] = "Плохое время - чтобы заводить пользователя - возвращайтесь позже!!";
               emergExit($response);
          }
     }

     $email = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : '';
     if(!preg_match("/^[a-z0-9\._]+\@[a-z]+\.[a-z]{2,4}$/i", $email)){
          $response['error'] = 1;
          $response['text'] = "Email не соответствует формату";
          emergExit($response);
     }else{
          if($result = mysqli_query($mysqli, 'SELECT * FROM SystemUsers WHERE Email=\''.$email.'\' LIMIT 1' )){
                if($result->num_rows > 0){
                       $response['error'] = 1;
                       $response['text'] = "Email существует!";
                       emergExit($response);
                }
          }else{
               $response['error'] = 1;
               $response['text'] = "Плохое время - чтобы заводить пользователя - возвращайтесь позже!!";
               emergExit($response);
          }
     }

     $orgPassword = generate_password(rand(8,12));
     $password = md5($orgPassword);

     $accesslevel = isset($_REQUEST['accesslevel']) ? intval($_REQUEST['accesslevel']) : 0;
     if($accesslevel < 0 ){
          $accesslevel = 0;
     }

     $accessarea = isset($_REQUEST['accessarea']) ? intval($_REQUEST['accessarea']) : 0;
     if($accesslevel > 1 ){
          $accesslevel = 1;
     }

     if($user_area <= 1){
           $parentId = $userid;
     }else{
           $parentId = isset($_REQUEST['parent']) ? intval($_REQUEST['parent']) : '';
     }

     $name = ( isset($_REQUEST['name']) && preg_match("/^[а-яё\- ]+$/ui", $_REQUEST['name'])) ? $_REQUEST['name'] : 'Аноним';

     $phone = ( isset($_REQUEST['phone']) && preg_match("/^[\+0-9 \-\(\)]+$/", $_REQUEST['phone'])) ? $_REQUEST['phone'] : '';

     $site = isset($_REQUEST['site']) ? intval($_REQUEST['site']) : '';
     if(!$site){
           $response['error'] = 1;
           $response['text'] = "Сайт обязан быть!!";
           emergExit($response);
     }

//     echo $login.','.$email.','.$password.','.$accesslevel.','.$accessarea.','.$parentId.','.$name.','.$phone.','.$site;
     mysqli_query($mysqli, "INSERT INTO SystemUsers (Login, Password, AccessLevel, AccessArea, MasterUserId, Email, Phone, Name, Locked, Deleted, SiteId) 
                                     VALUES ('".$login."', '".$password."', '".$accesslevel."', '".$accessarea."', '".$parentId."', '".$email."', '".$phone."', '".$name."', '1', NULL, '".$site."')");
     if($mysqli->affected_rows){
         foreach($sites as $s){
              if($s['id'] == $site){
                    $email_user = $s['email_user'];
                    $email_password = $s['email_password'];
                    $host = $s['host'];
                    if($host == 'i-sphere.ru') $host .= '/2.00/';
                    break;
              }
         }
         if(!$email_user || !$email_password){
               $response['error'] = 1;
               $response['text'] = "Email не может быть отправлен 0!";
               emergExit($response);
         }
         $subject = 'Активация учетной записи';

         $message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
         $message .= '<html xmlns="http://www.w3.org/1999/xhtml">';
         $message .= '<head><meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/><title>Активация аккаунта</title></head>';
         $message .= '<body>';
         $message .= 'Здравствуйте!<br/>';
         $message .= '<table cellspacing="0" cellpadding="0" width="100%" style="background-color: #ffffff;">';
         $message .= '<tr><td valign="top" style="background-color: #ffffff;"><b>Ссылка на активацию:</b> https://'.$host.'activate.php?token='.$password.'</td></tr>';
         $message .= '<tr><td valign="top" style="background-color: #ffffff;"><b>Логин: </b>'.$login.'</td></tr>';
         $message .= '<tr><td valign="top" style="background-color: #ffffff;"><b>Пароль: </b>'.$orgPassword.'</td></tr>';
         $message .= '</table></body></html>';

         sendMail($email_user, $email_password, $host, $email, $name, $subject, $message);

     }else{
           $response['error'] = 1;
           $response['text'] = "Плохое время - чтобы заводить пользователя - возвращайтесь позже!!";
           emergExit($response);
     }
     exit;
}



include('user/header.html');


//$user_level = get_user_level($mysqli);
//$user_access = get_user_access($mysqli);


//if($user_level == -1 || $user_access['users'] == 1){

      echo "<table><tr><td>";
      echo "<h1>Пользователи</h1>";
     // Дочерние пользователи

echo '<table><tr><td><div id="pager" class="pager">
<form>
<img src="user/addons/pager/icons/first.png" class="first"/>
<img src="user/addons/pager/icons/prev.png" class="prev"/>
<input type="text" class="pagedisplay"/>
<img src="user/addons/pager/icons/next.png" class="next"/>
<img src="user/addons/pager/icons/last.png" class="last"/>
<select class="pagesize">
<option selected="selected" value="10">10</option>
<option value="30">30</option>
<option value="100">100</option>
<option  value="500">500</option>
</select>
</form>
</div></td></tr></table>';

      $select = "SELECT * FROM isphere.SystemUsers";
      if ($user_area<=2) {
          $select .= " WHERE Id=$userid";
          if ($user_area>=1) {
              $select .= "  OR MasterUserId=$userid";
              if ($user_area>1) {
                  $select .= " OR MasterUserId IN (SELECT id FROM SystemUsers WHERE MasterUserId=$userid)";
              }
          }
      }
      $select .= " ORDER BY Login";
     if($result = mysqli_query($mysqli, $select)){
           if($result->num_rows > 0){
                echo '<table id="children"><thead><tr><td>Id</td><td>Login</td><td>Created</td><td>Email</td><td>Name</td><td>Phone</td><td>SiteId</td><td>ParentId</td><td>Access Level</td><td>Access Area</td><td>Locked</td><td>Deleted</td></tr></thead><tbody>';
                while( $row=$result->fetch_assoc() ){
                      echo '<tr id="ch_'.$row['Id'].'">';
                      echo '<td>'.$row['Id'].'</td>';
                      echo '<td>'.$row['Login'].'</td>';
                      echo '<td>'.$row['Created'].'</td>';
                      echo '<td class="clk" aKey="Email">'.$row['Email'].'</td>';
                      echo '<td class="clk" aKey="Name">'.$row['Name'].'</td>';
                      echo '<td class="clk" aKey="Phone">'.$row['Phone'].'</td>';
//                      echo '<td class="clk" aKey="SiteId">'.$row['SiteId'].'</td>';
                      echo '<td class="clkSel" aKey="SiteId">'.createSelector($mysqli,  'Site', 'site', 'id', 'host', $row['SiteId']).'</td>';
                      echo '<td class="clkSel" aKey="MasterUserId">'.($user_level == "-1" ? createSelector($mysqli, 'SystemUsers', 'parent', 'Id', 'Login', $row['MasterUserId']) : '').'</td>';
//                      echo '<td class="clk" aKey="AccessLevel">'.$row['AccessLevel'].'</td>';
                      echo '<td class="clkSel" aKey="AccessLevel">'.createSelector($mysqli, 'Access', 'accesslevel', 'level', 'name', $row['AccessLevel']).'</td>';
                      echo '<td class="clk" aKey="AccessArea">'.$row['AccessArea'].'</td>';
//                      echo '<td class="clk" aKey="Locked">'.$row['Locked'].'</td>';
                      echo '<td class="clkChk" aKey="Locked"><input type="checkbox" '.( $row['Locked'] > 0 ? 'checked="checked"' : '' ).'"></td>';
                      echo '<td class="clk" aKey="Deleted">'.$row['Deleted'].'</td>';
                }
                echo "</tbody></table>";
           }else{
                 echo "Вы пока не завели пользователей!";
           }
     }

      echo '</td><td valign="top" align="right">';

if($user_level == -1 || $user_access['users'] == 1){


      // Форма добавить пользователя

      $createForm = file_get_contents('user/form_create_user.html');

      $siteSelector = createSelector($mysqli, 'Site', 'site', 'id', 'host');
      $createForm = str_replace('###siteSelector###', $siteSelector, $createForm);

      $alSelector = createSelector($mysqli, 'Access', 'accesslevel', 'level', 'name');
      $createForm = str_replace('###accesslevelSelector###', $alSelector, $createForm);

      $parentSelector = $user_level == "-1" ? createSelector($mysqli, 'SystemUsers', 'parent', 'Id', 'Login') : '';
      $createForm = str_replace('###parentSelector###', $parentSelector, $createForm);

      echo $createForm;
      echo "<br />";
}
      echo "</tr></td></table>";


//echo '<a href="logout.php">Выйти</a><br/>';

include('footer.php');

?>