<?php

$data = [];
if (isset($_REQUEST['uploadfiles']) && isset($_REQUEST['ts'])) {
    $error = false;
    $files = [];

    $uploaddir = '/opt/bulk/';
    //    if( ! is_dir( $uploaddir ) ) mkdir( $uploaddir, 0777 );
    // переместим файлы из временной директории в указанную
    foreach ($_FILES as $file) {
        $valid_exts = ['txt', 'csv', 'xls', 'xlsx'];
        $ext = \pathinfo($file['name'], \PATHINFO_EXTENSION);
        $nameforfile = $_REQUEST['ts'].'.'.$ext;
        if (!\in_array(\strtolower($ext), $valid_exts)) {
            $error = "Неизвестный тип файла $ext. Поддерживаются только ".\implode(',', $valid_exts).'.';
        } elseif (\move_uploaded_file($file['tmp_name'], $uploaddir.$nameforfile)) {
            \chdir($uploaddir);
            \shell_exec('zip '.$_REQUEST['ts'].'.zip '.$nameforfile);
            \chmod($_REQUEST['ts'].'.zip', 0777);
            \unlink($nameforfile);
            $files[] = $file['name'];
        } else {
            $error = 'Ошибка записи файла на диск.';
        }
    }

    $data = $error ? ['error' => $error] : ['files' => $files];

    echo \json_encode($data);

    /*
        $serviceurl = "https://api.telegram.org/bot2103347962:AAHMdZY-Bh6ELR-NB7qOapnnD7sbh2c3bsQ/sendMessage?chat_id=-1001662664995";
        $msg = "Загружен файл реестра ".$data['files']." от пользователя ".$user->getUserIdentifier();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $serviceurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "text=".urlencode($msg));
        curl_setopt($ch, CURLOPT_POST, 1);

        $data = curl_exec($ch);

        $answer = $data;

        curl_close($ch);
    */
}
