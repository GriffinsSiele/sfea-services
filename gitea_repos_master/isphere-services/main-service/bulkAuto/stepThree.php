<?php

include 'config.php';
include '../config.php';
include '../auth.php';
require 'functions.php';

function makeSelector($i, $selected, $fields)
{
    // echo $selected."<br>";
    $selector = '<select name="'.$i.'" readonly><option>Не использовать</option>';
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

// print_r($_REQUEST);
// echo "<br>";

$login = $_SERVER['PHP_AUTH_USER'];

$fileName = $_REQUEST['filename'];
$workDir = '/opt/upload/'.$login.'_'.$_REQUEST['workFile'].'/';
$ext = \pathinfo($fileName, \PATHINFO_EXTENSION);

// echo '<br>'.$workDir.$fileName.'<br>';
if (!\file_exists($workDir.'request.'.$ext)) {
    echo 'Вы пытаетесь обработать несуществующий реестр!!';
    exit;
}

$fields = [];
foreach ($_REQUEST as $key => $val) {
    if (\preg_match("/^\d+$/", $key)) {
        $fields[$val] = $key;
    }
}

$available = [];

foreach ($sources as $source) {
    if (checkSource($source, $fields) && isset($user_sources[$source]) && 1 == $user_sources[$source]) {
        $available[] = $source;
    }
}

// print_r($available);

if (!\count($available)) {
    echo 'Для выбранных источников недостаточно данных!';
    exit;
}

// echo $workDir.$fileName."<br>";

$forSample = \array_slice(\explode("\n", \file_get_contents($workDir.'pre.csv')), 5, 10);

// print_r($forSample);

$sample = [];
foreach ($forSample as $string) {
    if (\preg_match_all("/\d{1,2}\/\d{1,2}\/\d{4}/", $string, $matches)) {
        foreach ($matches[0] as $v) {
            $string = \str_replace($v, \date('d.m.Y', \strtotime($v)), $string);
        }
    }
    $sample[] = \str_getcsv(\trim($string));
}

// print_r($sample);

if (!\is_array($sample) || \count($sample) < 1) {
    /*    echo "Если что-то пошло не так, напишите нам на <a href=\"mailto:support@i-sphere.ru\">support@i-sphere.ru</a>"; */
    echo 'Если что-то пошло не так, напишите в техподдержку';
} else {
    $columns = \count($sample[0]);
    $typesKeys = ['id' => 'Идентификатор', 'lastName' => 'Фамилия', 'firstName' => 'Имя', 'patronymic' => 'Отчество', 'fio' => 'ФИО', 'iof' => 'Имя Отчество Фамилия', 'bDate' => 'Дата рождения', 'region_id' => 'Код региона', 'serial' => 'Серия паспорта', 'number' => 'Номер паспорта', 'passport' => 'Паспорт (10 цифр)', 'inn' => 'ИНН', 'trash' => 'Прочие данные'];

    $tableHeaders = '';

    for ($i = 0; $i < $columns; ++$i) {
        $selected = \array_search($i, $fields);
        $tableHeaders .= '<th>'.makeSelector($i, $selected, $typesKeys).'</th>';
    }

    $content = '<html><head><title>Выбор источников</title><script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script></head><body><h1>Выбор источников</h1><form id="stepThree" action="stepFinal.php" method="POST"><input type="hidden" name="workFile" value="'.$_REQUEST['workFile'].'"><input type="hidden" name="filename" value="'.$fileName.'"><table> <thead><tr>'.$tableHeaders.'</tr></thead>';
    foreach ($sample as $string) {
        $content .= '<tr><td>'.\implode('</td><td>', $string).'</td></tr>';
    }

    $content .= '</table><br><br>';

    foreach ($available as $k => $v) {
        $content .= '<input type="checkbox" class="source" name="sources['.$v.']"> '.$v.'<br>';
    }

    $content .= '<br /><b>После подтерждения будут выполнены запросы в указанные источники по всем строкам реестра согласно действующим тарифам.</b><br />';
    $content .= '<br><br><input type="submit" value="Подтвердить и начать обработку"></form><script src="bulk.js"></script></body></html>';

    echo $content;
}
