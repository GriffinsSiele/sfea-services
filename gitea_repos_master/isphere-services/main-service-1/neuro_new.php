<?php

$nn_minacc = array(
    '' => 0.5,
    'fssp' => 0.1,
    'vk' => 0.1,
    'getcontact' => 0.7,
    'gosuslugi' => 0.5,
);

//$nn_services = explode(',', getenv('IMAGE_CAPTCHA_URLS'));

$nn_services = array(
//    'http://captcha-image-api.i-sphere',
    'http://172.16.0.65',
    'http://172.16.0.1',
);

$nn_trytimeout = [2,1,1];
$nn_error = 0;

$neuro_sources = array(
//    'emt' => 'emt',
    'fns_bi' => 'fns',
    'fns_svl' => 'fns',
    'mvd_wanted' => 'mvd',
    'fsspsite' => 'fsspsite',
    'getcontact_app' => 'gc',
    'vk' => 'vk',
    'vk_recover' => 'vk',
    'fms' => 'fms',
    'gosuslugi' => 'gosuslugi',
    'gibdd' => 'gibdd',
    'fotostrana' => 'fotostrana',
);

$neuro_methods = array(
//    'emt' => 'emt',
    'fnsdecode' => 'fns',
    'mvddecode' => 'mvd',
    'fsspsitedecode' => 'fssp',
    'gcdecode' => 'getcontact',
    'vkdecode' => 'vk',
    'fmsdecode' => 'fms',
    'gosuslugidecode' => 'gosuslugi',
    'gibdddecode' => 'gibdd',
    'fotostranadecode' => 'fotostrana',
);

$neuro_minacc = array(
    '' => 0.5,
//    'fmsdecode' => 0.5,
//    'fnsdecode' => 0.5,
//    'mvddecode' => 0.5,
    'fsspsitedecode' => 0.2,
    'vkdecode' => 0.2,
);

$neuro_services = explode(',', getenv('TEXT_CAPTCHA_URLS'));
$neuro_tries = 3;
$neuro_timeout = 1;

$token_services = array(
//    'http://172.16.1.14:8003',
//    'http://172.16.1.253:8003',
    'http://172.16.1.254:8003',
);
$token_tries = 2;
$token_timeout = 1;


function nn_decode($image, $source, $provider='nnetworks')
{
    global $nn_services,$nn_minacc,$nn_trytimeout,$nn_error,$reqId;
    if ($nn_error>=3) { // после 3 ошибок подряд даже не пытаемся
        return 'ERROR_DISABLED';
    }
    $start = microtime(true);
    $boundary = '--------------------------'.microtime(true);
    $post_data = "--".$boundary."\r\n".
        "Content-Disposition: form-data; name=\"image\"; filename=\"captcha.jpg\"\r\n".
        "Content-Type: image/jpeg\r\n\r\n".
        $image."\r\n".
        "--".$boundary."--\r\n";
    $tries = 0;
    do {
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: multipart/form-data; boundary='.$boundary,
                'content' => $post_data,
                'timeout' => $nn_trytimeout[$tries],
            )
        ));
        $result = @file_get_contents(preg_replace("/\/api\/decode\/image$/","",$nn_services[$tries%sizeof($nn_services)])."/api/decode/image?provider=$provider&source=$source&timeout=0",false,$context);
        if ($result) {
            $nn_error=0;
        } else {
            $nn_error++;
            $error = error_get_last();
            file_put_contents('./logs/nn-new-'.date('Y-m-d').'.log',date('Y-m-d H:i:s')." id=$reqId source=$source timeout=".$nn_trytimeout[$tries]." errors=$nn_error {$error['message']}\n",FILE_APPEND);
        }
    } while (!$result && ++$tries<sizeof($nn_trytimeout));
    $process_time = number_format(microtime(true)-$start,2,'.','');
    file_put_contents('./logs/nn-new-'.date('Y-m-d').'.log',date('Y-m-d H:i:s')." id=$reqId source=$source processtime=$process_time $result\n",FILE_APPEND);
    $res = json_decode($result, true);
    return $res;
}

function nn_post($image, $source, $minacc=0)
{
    global $nn_minacc;
    $res = nn_decode($image, $source);
    if (!$res) {
        return 'ERROR_NO_ANSWER';
    } elseif (!isset($res['text'])) {
        return 'ERROR_CAPTCHA_UNSOLVABLE';
    }
    if ($minacc==0) $minacc = isset($nn_minacc[$source])?$nn_minacc[$source]:$nn_minacc[''];
//    echo "Neuro $source: $result min=$minacc\n";
    if (isset($res['accuracy']) && $res['accuracy']<$minacc) {
        return 'ERROR_CAPTCHA_INNACURATE';
    }
    return strtr($res['text'],array(' '=>'','-'=>'9','[UNK]'=>'9'));
}

function neuro_post($image, $method)
{
    global $neuro_methods;
    if (isset($neuro_methods[$method])) {
        return nn_post($image, $neuro_methods[$method]);
    } else {
        return 'ERROR_UNKNOWN_SOURCE';
    }
}
/*
function neuro_post($image, $method)
{
//    return 'ERROR_DISABLED';
    global $neuro_services,$neuro_minacc,$neuro_tries,$neuro_timeout;
    $boundary = '--------------------------'.microtime(true);
    $post_data = "--".$boundary."\r\n".
        "Content-Disposition: form-data; name=\"image\"; filename=\"captcha.jpg\"\r\n".
        "Content-Type: image/jpeg\r\n\r\n".
        $image."\r\n".
        "--".$boundary."--\r\n";
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-Type: multipart/form-data; boundary='.$boundary,
            'content' => $post_data,
            'timeout' => $neuro_timeout,
         )
    ));
    $tries = 0;
    do {
       $result = file_get_contents($neuro_services[$tries%sizeof($neuro_services)]."/$method",false,$context);
    } while (!$result && ++$tries<=$neuro_tries);
    $res = json_decode($result, true);
    if (!$res) {
        return 'ERROR_BAD_ANSWER';
    }
//    echo "Neuro $method: $result min=".(isset($neuro_minacc[$method])?$neuro_minacc[$method]:$neuro_minacc[''])."\n";
    if (isset($res['acc']) && $res['acc']<(isset($neuro_minacc[$method])?$neuro_minacc[$method]:$neuro_minacc[''])) {
        return 'ERROR_CAPTCHA_INNACURATE';
    }
    return strtr($res['text'],array(' '=>'','-'=>'9','[UNK]'=>'9'));
}
*/
function neuro_token($method,$sitekey,$action='')
{
    global $token_services,$token_tries,$token_timeout;
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'timeout' => $token_timeout,
         )
    ));
    $tries = 0;
    do {
       $result = file_get_contents($token_services[$tries%sizeof($token_services)]."/$method?sitekey=$sitekey".($action?"&action=$action":""),false,$context);
    } while (!$result && ++$tries<=$token_tries);
    $res = json_decode($result, true);
    if (!$res) {
        return 'ERROR_BAD_ANSWER';
    }
    return $res['code']==200?$res['token']:'ERROR_NO_TOKEN';
}

?>
