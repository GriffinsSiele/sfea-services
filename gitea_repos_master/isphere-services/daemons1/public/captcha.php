<?php

$captcha_proxy = false;
/*
$captcha_proxy = array(
    'host'=>'45.129.6.99:8000',
    'auth'=>base64_encode('1YJASa:5ZksWu')
);
*/

function captcha_create(
    $type = 'ImageToTextTask',
    $image = false,
    $sitekey = false,
    $page = false,
    $action = false,
    $minscore = 0,
    $apikey = false,
    $is_verbose = false,
    $sendhost = 'api.anti-captcha.com',
    $is_phrase = 0,
    $is_regsense = 0,
    $is_numeric = 0,
    $is_math = 0,
    $min_len = 0,
    $max_len = 0,
    $is_russian = 0)
{
    if ($is_verbose) echo "is_numeric: $is_numeric\n";
    if ($is_verbose) echo "is_russian: $is_russian\n";

    if ($type=='recaptcha' || $type=='v2') $type='NoCaptchaTaskProxyless';
    if ($type=='enterprise' || $type=='v2ent') $type='RecaptchaV2EnterpriseTaskProxyless';
    if ($type=='recaptchav3' || $type=='v3') $type='RecaptchaV3TaskProxyless';
    if ($type=='hcaptcha') $type='HCaptchaTaskProxyless';
    if ($type=='turnstile') $type='TurnstileTaskProxyless';

    $postdata = array(
        'clientKey'  => $apikey,
        'task' => array(
            'type'     => $type,
            'phrase'     => $is_phrase&&1,
            'regsense'   => $is_regsense&&1,
            'Case'       => $is_regsense&&1,
            'numeric'    => $is_numeric,
            'math'       => $is_math&&1,
            'minLength'  => $min_len,
            'minLength'  => $max_len,
            'recognizingThreshold' => 90,
        ),
        'languagePool'   => $is_russian?'ru':'en',
    );

    if ($image) {
        $postdata['task']['body'] = base64_encode($image);
    }
    if ($sitekey) {
        $postdata['task']['websiteKey'] = $sitekey;
    }
    if ($page) {
        $postdata['task']['websiteURL'] = $page;
    }
    if ($action) {
        $postdata['task']['pageAction'] = $action;
    }
    if ($minscore) {
        $postdata['task']['minScore'] = $minscore;
    }
    $poststr=json_encode($postdata);

    $options = array('http' => array(
        'method' => 'POST',
        'content' => $poststr,
        'timeout' => 3,
        'header' =>
            "Content-Type: application/json\r\n" .
            "Content-Length: ".strlen($poststr)."\r\n" .
            "Accept: application/json\r\n"
    ));

    global $captcha_proxy;
    if ($captcha_proxy) {
        $options['http']['proxy'] = $captcha_proxy['host'];
        $options['http']['request_fulluri'] = true;
        if ($captcha_proxy['auth'])
            $options['http']['header'] .= "Proxy-Authorization: Basic {$captcha_proxy['auth']}\r\nAuthorization: Basic {$captcha_proxy['auth']}\r\n";
        if ($is_verbose) echo "Using proxy {$captcha_proxy['host']}";
    }
    $context = stream_context_create($options);
    $result = @file_get_contents("http://$sendhost/createTask",false,$context);
    if ($is_verbose) echo "received result $result\n";
    if (!$result) {
        if ($is_verbose) echo "server did not answered\n";
        return 'ERROR_NOANSWER';
    }
    $ex = json_decode($result, true);
    if (!$ex) {
        if ($is_verbose) echo "server returned invalid answer\n";
        return 'ERROR_INVALIDANSWER';
    }
    if (isset($ex['errorCode']) && $ex['errorCode']) {
        if ($is_verbose) echo "server returned error: {$ex['errorCode']} {$ex['errorDescription']} \n";
        if (($type=='recaptchav3' || $type=='v3') && ($minscore<0.9) && ($ex['errorCode']=='ERROR_NO_SLOT_AVAILABLE'))
            return captcha_create($type,$image,$sitekey,$page,$action,$minscore<0.7?0.7:0.9,$apikey,$is_verbose,$sendhost);
        return $ex['errorCode'];
    }
    if (!isset($ex['taskId'])) {
        if ($is_verbose) echo "server returned answer without id: $result\n";
        return "ERROR_TASK";
    }
    $captcha_id = $ex['taskId'];
    if ($is_verbose) echo "captcha sent, got captcha ID $captcha_id\n";
    return $captcha_id;
}

function captcha_result(
    $captcha_id,
    $apikey,
    $is_verbose = false,
    $sendhost = 'api.anti-captcha.com')
{
    $postdata = array(
        'clientKey'  => $apikey,
        'taskId' => $captcha_id,
    );

    $poststr=json_encode($postdata);

    $options = array('http' => array(
        'method' => 'POST',
        'content' => $poststr,
        'timeout' => 3,
        'header' =>
            "Content-Type: application/json\r\n" .
            "Content-Length: ".strlen($poststr)."\r\n" .
            "Accept: application/json\r\n"
    ));

    global $captcha_proxy;
    if ($captcha_proxy) {
        $options['http']['proxy'] = $captcha_proxy['host'];
        $options['http']['request_fulluri'] = true;
        if ($captcha_proxy['auth'])
            $options['http']['header'] .= "Proxy-Authorization: Basic {$captcha_proxy['auth']}\r\nAuthorization: Basic {$captcha_proxy['auth']}\r\n";
        if ($is_verbose) echo "Using proxy {$captcha_proxy['host']}";
    }
    $context = stream_context_create($options);
    $result = @file_get_contents("http://$sendhost/getTaskResult",false,$context);
    if (!$result) {
        if ($is_verbose) echo "server did not answered\n";
        return '';
    }
    $ex = json_decode($result, true);
    if (!$ex) {
        if ($is_verbose) echo "server returned invalid answer\n";
        return 'ERROR_INVALIDANSWER';
    }
    if ($ex['errorId']) {
        if ($is_verbose) echo "server returned error: {$ex['errorCode']} {$ex['errorDescription']} \n";
        return $ex['errorCode'];
    }
    if (!isset($ex['status']) || !isset($ex['errorId'])) {
        if ($is_verbose) echo "server returned answer without status: $result\n";
        return "ERROR_STATUS";
    }

    if ($ex['status']!=='ready') {
        if ($is_verbose) echo "captcha is not ready yet\n";
        return false;
    }
    if (!isset($ex['solution'])) {
        if ($is_verbose) echo "server returned answer without solution: $result\n";
        return "ERROR_SOLUTION";
    }
    if (isset($ex['solution']['text'])) {
        $value = trim($ex['solution']['text']);
        if ($is_verbose) echo "captcha recognized as $value\n";
        return $value;
    } elseif (isset($ex['solution']['gRecaptchaResponse'])) {
        $value = trim($ex['solution']['gRecaptchaResponse']);
        if ($is_verbose) echo "recaptcha response is $value\n";
        return $value;
    } else {
        if ($is_verbose) echo "server returned answer with unknown solution: $result\n";
        return "UNKNOWN_ERROR";
    }
}

function captcha_bad(
    $captcha_id,
    $apikey,
    $is_verbose = false,
    $sendhost = 'api.anti-captcha.com')
{
    $postdata = array(
        'clientKey'  => $apikey,
        'taskId' => $captcha_id,
    );

    $poststr=json_encode($postdata);

    $options = array('http' => array(
        'method' => 'POST',
        'content' => $poststr,
        'timeout' => 3,
        'header' =>
            "Content-Type: application/json\r\n" .
            "Content-Length: ".strlen($poststr)."\r\n" .
            "Accept: application/json\r\n"
    ));

    global $captcha_proxy;
    if ($captcha_proxy) {
        $options['http']['proxy'] = $captcha_proxy['host'];
        $options['http']['request_fulluri'] = true;
        if ($captcha_proxy['auth'])
            $options['http']['header'] .= "Proxy-Authorization: Basic {$captcha_proxy['auth']}\r\nAuthorization: Basic {$captcha_proxy['auth']}\r\n";
        if ($is_verbose) echo "Using proxy {$captcha_proxy['host']}";
    }
    $context = stream_context_create($options);
    $result = @file_get_contents("http://$sendhost/reportIncorrectImageCaptcha",false,$context);
    if (!$result) {
        if ($is_verbose) echo "server did not answered\n";
        return 'ERROR_NOANSWER';
    }
    $ex = json_decode($result, true);
    if (!$ex) {
        if ($is_verbose) echo "server returned invalid answer\n";
        return 'ERROR_INVALIDANSWER';
    }
    if ($ex['errorId']) {
        if ($is_verbose) echo "server returned error: {$ex['errorCode']}\n";
        return 'ERROR_INVALIDTASK';
    }
    if (!isset($ex['status']) || !isset($ex['errorId'])) {
        if ($is_verbose) echo "server returned answer without status: $result\n";
        return "ERROR_STATUS";
    }

    if ($is_verbose) echo "Captcha report accepted\n";
    return 'OK_REPORT_RECORDED';
}

function recaptcha_good(
    $captcha_id,
    $apikey,
    $is_verbose = false,
    $sendhost = 'api.anti-captcha.com')
{
    $postdata = array(
        'clientKey'  => $apikey,
        'taskId' => $captcha_id,
    );

    $poststr=json_encode($postdata);

    $options = array('http' => array(
        'method' => 'POST',
        'content' => $poststr,
        'timeout' => 3,
        'header' =>
            "Content-Type: application/json\r\n" .
            "Content-Length: ".strlen($poststr)."\r\n" .
            "Accept: application/json\r\n"
    ));

    global $captcha_proxy;
    if ($captcha_proxy) {
        $options['http']['proxy'] = $captcha_proxy['host'];
        $options['http']['request_fulluri'] = true;
        if ($captcha_proxy['auth'])
            $options['http']['header'] .= "Proxy-Authorization: Basic {$captcha_proxy['auth']}\r\nAuthorization: Basic {$captcha_proxy['auth']}\r\n";
        if ($is_verbose) echo "Using proxy {$captcha_proxy['host']}";
    }
    $context = stream_context_create($options);
    $result = @file_get_contents("http://$sendhost/reportCorrectRecaptcha",false,$context);
    if (!$result) {
        if ($is_verbose) echo "server did not answered\n";
        return 'ERROR_NOANSWER';
    }
    $ex = json_decode($result, true);
    if (!$ex) {
        if ($is_verbose) echo "server returned invalid answer\n";
        return 'ERROR_INVALIDANSWER';
    }
    if ($ex['errorId']) {
        if ($is_verbose) echo "server returned error: {$ex['errorCode']}\n";
        return 'ERROR_INVALIDTASK';
    }
    if (!isset($ex['status']) || !isset($ex['errorId'])) {
        if ($is_verbose) echo "server returned answer without status: $result\n";
        return "ERROR_STATUS";
    }

    if ($is_verbose) echo "Captcha report accepted\n";
    return 'OK_REPORT_RECORDED';
}

function recaptcha_bad(
    $captcha_id,
    $apikey,
    $is_verbose = false,
    $sendhost = 'api.anti-captcha.com')
{
    $postdata = array(
        'clientKey'  => $apikey,
        'taskId' => $captcha_id,
    );

    $poststr=json_encode($postdata);

    $options = array('http' => array(
        'method' => 'POST',
        'content' => $poststr,
        'timeout' => 3,
        'header' =>
            "Content-Type: application/json\r\n" .
            "Content-Length: ".strlen($poststr)."\r\n" .
            "Accept: application/json\r\n"
    ));

    global $captcha_proxy;
    if ($captcha_proxy) {
        $options['http']['proxy'] = $captcha_proxy['host'];
        $options['http']['request_fulluri'] = true;
        if ($captcha_proxy['auth'])
            $options['http']['header'] .= "Proxy-Authorization: Basic {$captcha_proxy['auth']}\r\nAuthorization: Basic {$captcha_proxy['auth']}\r\n";
        if ($is_verbose) echo "Using proxy {$captcha_proxy['host']}";
    }
    $context = stream_context_create($options);
    $result = @file_get_contents("http://$sendhost/reportIncorrectRecaptcha",false,$context);
    if (!$result) {
        if ($is_verbose) echo "server did not answered\n";
        return 'ERROR_NOANSWER';
    }
    $ex = json_decode($result, true);
    if (!$ex) {
        if ($is_verbose) echo "server returned invalid answer\n";
        return 'ERROR_INVALIDANSWER';
    }
    if ($ex['errorId']) {
        if ($is_verbose) echo "server returned error: {$ex['errorCode']}\n";
        return 'ERROR_INVALIDTASK';
    }
    if (!isset($ex['status']) || !isset($ex['errorId'])) {
        if ($is_verbose) echo "server returned answer without status: $result\n";
        return "ERROR_STATUS";
    }

    if ($is_verbose) echo "Captcha report accepted\n";
    return 'OK_REPORT_RECORDED';
}

?>