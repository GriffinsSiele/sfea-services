<?php

ini_set('memory_limit', '2000M');
include('config.php');
include('../config.php');
include('../auth.php');
require('functions.php');

function makeSelector($i, $selected, $fields){
    //echo $selected."<br>";
    $selector = '<select name="'.$i.'" readonly>';
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

$user_access = get_user_access($mysqli);
if (!$user_access['bulk']) {
    echo json_encode(array('error'=>'У вас нет доступа к загрузке файлов!'));
    exit;
}

$user_sources = get_user_sources($mysqli);

//print_r($_REQUEST);
//echo "<br>";

$login = $_SERVER['PHP_AUTH_USER'];

$fileName = $_REQUEST['filename'];
$workDir = '/opt/upload/'.$login.'_'.$_REQUEST['workFile'].'/';
$ext = pathinfo($fileName, PATHINFO_EXTENSION);

//echo '<br>'.$workDir.$fileName.'<br>';
if(!file_exists($workDir.'request.'.$ext)){
    echo "Вы пытаетесь обработать несуществующий реестр!!";
    exit;
}

$fields = array();
foreach($_REQUEST as $key => $val){
    if(preg_match("/^\d+$/", $key)){
        $fields[$val.($val=="other"?$key:"")] = $key;
    }
}

$checktypes = array();
//$sourcesfilter = "IN ('".implode("','",$autosources)."')";
if($result = mysqli_query($mysqli, 'SELECT * FROM CheckType WHERE status>0'./*($login=='av'||true?"":" AND (code $sourcesfilter OR source_code $sourcesfilter)").*/' ORDER BY code')){
    while($row = $result->fetch_assoc()){
        $checktypes[$row['code']] = $row;
    }
    mysqli_free_result($result);
}

$sources = array();
foreach($checktypes as $code => $checktype){
//    echo "$code ".(checkSource($code, $fields)?'1':'0')."</br>";
    if( (checkSource($code, $fields)/* || checkSource($checktype['source_code'], $fields)*/) && ((isset($user_sources[$code]) && $user_sources[$code]) || (isset($user_sources[$checktype['source_code']]) && $user_sources[$checktype['source_code']]))){
         $sources[] = $code;
    }
}
//print_r($sources);

if(!count($sources)){
    echo "Для выбранных источников недостаточно данных!";
    exit;
}

//echo $workDir.$fileName."<br>";

$forSample=array_slice(explode("\n", file_get_contents($workDir.'pre.csv')),0,10);

//print_r($forSample);

$sample = array();
foreach($forSample as $string){
    if(preg_match_all( "/\d{1,2}\/\d{1,2}\/\d{4}/", $string, $matches)){
        foreach($matches[0] as $v){
            $string = str_replace($v, date('d.m.Y',strtotime($v)), $string);
        }
    }
    $sample[] = str_getcsv(trim($string));
}

//print_r($sample);

if(!is_array($sample) || count($sample) < 1){
/*    echo "Если что-то пошло не так, напишите нам на <a href=\"mailto:support@i-sphere.ru\">support@i-sphere.ru</a>";*/
    echo "Если что-то пошло не так, напишите в техподдержку";
}else{

    $columns = count($sample[0]);
    $typesKeys = array('skip'=>'Не использовать', 'id'=>'Идентификатор', 'other'=>'Доп.идентификатор', 'lastName'=>'Фамилия', 'firstName'=>'Имя', 'patronymic'=>'Отчество', 'fio'=>'Фамилия Имя Отчество', 'iof'=>'Имя Отчество Фамилия', 'bDate'=>'Дата рождения', 'region_id'=>'Код региона', 'serial'=>'Серия паспорта', 'number'=>'Номер паспорта','passport'=>'Паспорт (10 цифр)', 'inn'=>'ИНН физлица/ИП', 'orginn'=>'ИНН юрлица', 'phone'=>'Телефон', 'email'=>'E-mail', 'vin'=>'VIN');

    $tableHeaders = '';

    for($i=0; $i<$columns; $i++){
        $selected = array_search($i, $fields);
        $tableHeaders .= '<th>'.makeSelector($i, $selected, $typesKeys).'</th>';
    }

    $content = '<html><head><title>Выбор источников</title><script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script></head><body><h1>Выбор источников</h1><form id="stepThree" action="stepFinal.php" method="POST"><input type="hidden" name="workFile" value="'.$_REQUEST['workFile'].'"><input type="hidden" name="filename" value="'.$fileName.'"><table> <thead><tr>'.$tableHeaders.'</tr></thead>';
    foreach($sample as $string){
        $content .= '<tr><td>'.implode('</td><td>', $string).'</td></tr>';
    }

    $content .= '</table><br><br>';

    foreach($sources as $code){
        $content .= '<input type="checkbox" class="source" name="sources['.$code.']"> <b>'.$code.'</b> ('.$checktypes[$code]['title'].')<br>';
    }

    $content .= '<br /><b>После подтверждения будут выполнены запросы в указанные источники по всем строкам реестра согласно действующим тарифам.</b><br />';
    $content .= '<br><br><input type="submit" value="Подтвердить и начать обработку"></form><script src="bulk.js"></script></body></html>';

    echo $content;
}

include('../footer.php');
