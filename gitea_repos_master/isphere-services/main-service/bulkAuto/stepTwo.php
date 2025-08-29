<?php

include '../config.php';
include '../auth.php';
require 'functions.php';
require __DIR__.'/vendor/autoload.php';

$info = 'Выберите в заголовках нужный тип данных. Обязательно поставьте "Не использовать" в столбцах с лишними данными.<br/>Обратите внимание, что ФИО - это Фамилия Имя Отчество, не перепутайте с вариантом "Имя Отчество Фамилия".';

function makeSelector($i, $selected, $fields)
{
    // echo $selected."<br>";
    $selector = '<select name="'.$i.'"><option>Не использовать</option>';
    foreach ($fields as $field => $val) {
        if ($field == $selected) {
            $selector .= '<option value="'.$field.'" selected>'.$val.'</option>';
        } else {
            $selector .= '<option value="'.$field.'">'.$val.'</option>';
        }
    }
    $selector .= '</select>';

    return $selector;
}

\set_time_limit($total_timeout * 10);

$user_access = get_user_access($mysqli);
if (!$user_access['bulk']) {
    echo \json_encode(['error' => 'У вас нет доступа к загрузке файлов!']);
    exit;
}

$user_sources = get_user_sources($mysqli);
if (!\in_array('fssp', $user_sources) && !\in_array('fns', $user_sources) && !\in_array('bankrot', $user_sources) && !\in_array('notariat', $user_sources)) {
    echo \json_encode(['error' => 'У вас нет доступа к источникам для автоматической обработки реестров!']);
    exit;
}

$login = $_SERVER['PHP_AUTH_USER'];

$uploaddir = '/opt/upload/'.$login.'_'.$_SERVER['REQUEST_TIME'].'/';
if (!\is_dir($uploaddir)) {
    \mkdir($uploaddir, 0777);
}
// переместим файлы из временной директории в указанную
$file = $_FILES['file'];

$valid_exts = ['txt', 'csv', 'xls', 'xlsx'];
$ext = \pathinfo($file['name'], \PATHINFO_EXTENSION);
$nameforfile = 'request.'.$ext;
if (!\in_array(\strtolower($ext), $valid_exts)) {
    echo "Неизвестный тип файла $ext. Поддерживаются только ".\implode(',', $valid_exts).'.';
} elseif (\move_uploaded_file($file['tmp_name'], $uploaddir.$nameforfile)) {
    // echo $uploaddir;
    // echo "<br>";
    //    echo "<pre>";
    $sample = preWork($uploaddir);
    if (!\is_array($sample) || \count($sample) < 1) {
        /*    echo "Если что-то пошло не так, напишите нам на <a href=\"mailto:support@i-sphere.ru\">support@i-sphere.ru</a>"; */
        echo 'Если что-то пошло не так, напишите в техподдержку';
    } else {
        // print_r($sample[0]);
        // echo "<br>";
        $columns = \count($sample[0]);
        $typesKeys = ['id' => 'Идентификатор', 'lastName' => 'Фамилия', 'firstName' => 'Имя', 'patronymic' => 'Отчество', 'fio' => 'ФИО', 'iof' => 'Имя Отчество Фамилия', 'bDate' => 'Дата рождения', 'region_id' => 'Код региона', 'serial' => 'Серия паспорта', 'number' => 'Номер паспорта', 'passport' => 'Паспорт (10 цифр)', 'inn' => 'ИНН', 'trash' => 'Прочие данные'];
        // echo $uploaddir.$nameforfile."<br>";
        $fields = \json_decode(\file_get_contents($uploaddir.'fields.txt'), true);
        // print_r($fields);

        $tableHeaders = '';
        // echo $columns."<br>";
        for ($i = 0; $i < $columns; ++$i) {
            // echo $i."<br>";
            $selected = \array_search($i, $fields);
            // echo $selected."<br>";
            $tableHeaders .= '<th>'.makeSelector($i, $selected, $typesKeys).'</th>';
        }

        $content = '<html><head><title>Подтверждение типов данных</title></head><body><h1>Подтверждение типов данных</h1><br><b>'.$info.'</b><br><br><form action="stepThree.php" method="POST"><input type="hidden" name="workFile" value="'.$_SERVER['REQUEST_TIME'].'"><input type="hidden" name="filename" value="'.$file['name'].'"><table> <thead><tr>'.$tableHeaders.'</tr></thead>';
        foreach ($sample as $string) {
            $content .= '<tr><td>'.\implode('</td><td>', $string).'</td></tr>';
        }
        $content .= '</table><br><input type="submit" value="Продолжить"></form></body></html>';
        echo $content;
    }
} else {
    echo 'Ошибка записи файла на диск.';
}

?>


