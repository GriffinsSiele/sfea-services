<?php

ini_set('memory_limit', '2000M');
include('../config.php');
include('../auth.php');
require('functions.php');
require __DIR__.'/vendor/autoload.php';

$info = 'Выберите в заголовках нужный тип данных.<br/>Не перепутайте варианты "Фамилия Имя Отчество" и "Имя Отчество Фамилия".<br/>Обязательно поставьте "Не использовать" в столбцах с лишними данными.';

function makeSelector($i, $selected, $fields){
    //echo $selected."<br>";
    $selector = '<select name="'.$i.'">';
    foreach($fields as $field => $val){
        if($field == substr($selected,0,strlen($field))){
            $selector .= '<option value="'.$field.'" selected>'.$val.'</option>';
        }else{
            $selector .= '<option value="'.$field.'">'.$val.'</option>';
        }
    }
    $selector .= '</select>';
    return $selector;
}

set_time_limit($total_timeout*10);

$user_access = get_user_access($mysqli);
if (!$user_access['bulk']) {
    echo json_encode(array('error'=>'У вас нет доступа к загрузке файлов!'));
    exit;
}

$user_sources = get_user_sources($mysqli);

$login = $_SERVER['PHP_AUTH_USER'];

$uploaddir = '/opt/upload/'.$login.'_'.$_SERVER['REQUEST_TIME'].'/';
if( ! is_dir( $uploaddir ) ) mkdir( $uploaddir );
// переместим файлы из временной директории в указанную
$file = $_FILES['file'];

$valid_exts = array('txt','csv','xls','xlsx');
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$nameforfile = 'request.'.$ext;
if (!in_array(strtolower($ext),$valid_exts)) {
    echo "Неизвестный тип файла $ext. Поддерживаются только ".implode(',',$valid_exts).".";
} elseif (move_uploaded_file( $file['tmp_name'], $uploaddir.$nameforfile )) {
    $msg = "Загружен файл реестра ".$file['name']." от пользователя ".$_SERVER['PHP_AUTH_USER'];
    telegramMsg($msg);
    //echo $uploaddir;
    //echo "<br>";
//    echo "<pre>";
    $sample =  preWork($uploaddir);
    if(!is_array($sample) || count($sample) < 1 ){
/*    echo "Если что-то пошло не так, напишите нам на <a href=\"mailto:support@i-sphere.ru\">support@i-sphere.ru</a>";*/
    echo "Если что-то пошло не так, напишите в техподдержку";
    }else{
         //print_r($sample[0]);
         // echo "<br>";
         $columns = count($sample[0]);
         $typesKeys = array('skip'=>'Не использовать', 'id'=>'Идентификатор', 'other'=>'Доп.идентификатор', 'lastName'=>'Фамилия', 'firstName'=>'Имя', 'patronymic'=>'Отчество', 'fio'=>'Фамилия Имя Отчество', 'iof'=>'Имя Отчество Фамилия', 'bDate'=>'Дата рождения', 'region_id'=>'Код региона', 'serial'=>'Серия паспорта', 'number'=>'Номер паспорта','passport'=>'Паспорт (10 цифр)', 'inn'=>'ИНН физлица/ИП', 'orginn'=>'ИНН юрлица', 'phone'=>'Телефон', 'email'=>'E-mail', 'vin'=>'VIN');
         //echo $uploaddir.$nameforfile."<br>";
         $fields = json_decode(file_get_contents($uploaddir.'fields.txt'), true);
         //print_r($fields);

         $tableHeaders = '';
         //echo $columns."<br>";
         for($i=0; $i<$columns; $i++){
             //echo $i."<br>";
             $selected = array_search($i, $fields);
             //echo $selected."<br>";
             $tableHeaders .= '<th>'.makeSelector($i, $selected, $typesKeys).'</th>';
         }

         $content = '<html><head><title>Подтверждение типов данных</title></head><body><h1>Подтверждение типов данных</h1><br><b>'.$info.'</b><br><br><form action="stepThree.php" method="POST"><input type="hidden" name="workFile" value="'.$_SERVER['REQUEST_TIME'].'"><input type="hidden" name="filename" value="'.$file['name'].'"><table> <thead><tr>'.$tableHeaders.'</tr></thead>';
         foreach($sample as $string){
             $content .= '<tr><td>'.implode('</td><td>', $string).'</td></tr>';
         }
         $content .= '</table><br><input type="submit" value="Продолжить"></form></body></html>';
         echo $content;
    }
} else {
    echo 'Ошибка записи файла на диск.';
}

include('../footer.php');
