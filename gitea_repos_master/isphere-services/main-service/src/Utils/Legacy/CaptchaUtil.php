<?php

declare(strict_types=1);

namespace App\Utils\Legacy;

class CaptchaUtil
{
    public function captcha_create(
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
        if ($is_verbose) {
            echo "is_numeric: $is_numeric\n";
        }
        if ($is_verbose) {
            echo "is_russian: $is_russian\n";
        }

        if ('recaptcha' == $type) {
            $type = 'NoCaptchaTaskProxyless';
        }
        if ('recaptchav3' == $type || 'v3' == $type) {
            $type = 'RecaptchaV3TaskProxyless';
        }
        if ('hcaptcha' == $type) {
            $type = 'HCaptchaTaskProxyless';
        }

        $postdata = [
            'clientKey' => $apikey,
            'task' => [
                'type' => $type,
                'phrase' => $is_phrase && 1,
                'regsense' => $is_regsense && 1,
                'Case' => $is_regsense && 1,
                'numeric' => $is_numeric,
                'math' => $is_math && 1,
                'minLength' => $min_len,
                'minLength' => $max_len,
                'recognizingThreshold' => 90,
            ],
            'languagePool' => $is_russian ? 'ru' : 'en',
        ];

        if ($image) {
            $postdata['task']['body'] = \base64_encode($image);
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
        $poststr = \json_encode($postdata);

        if ($is_verbose) {
            echo 'connecting to host...';
        }
        $fp = \fsockopen($sendhost, 80, $errnum, $errmsg, 2);
        if (false != $fp) {
            if ($is_verbose) {
                echo "OK\n";
            }
            if ($is_verbose) {
                echo 'sending request...';
            }
            $header = "POST /createTask HTTP/1.0\r\n";
            $header .= "Host: $sendhost\r\n";
            $header .= "Accept: application/json\r\n";
            $header .= "Content-Type: application/json\r\n";
            $header .= 'Content-Length: '.\strlen($poststr)."\r\n";
            $header .= "\r\n$poststr\r\n";
            // if ($is_verbose) echo $header;
            // exit;
            \fwrite($fp, $header);
            if ($is_verbose) {
                echo "OK\n";
            }
            if ($is_verbose) {
                echo 'getting response...';
            }
            $resp = '';
            \stream_set_timeout($fp, 5);
            do {
                $resp .= \fgets($fp, 4096);
                $info = \stream_get_meta_data($fp);
            } while (!\feof($fp) && !$info['timed_out']);
            \fclose($fp);
            if ($info['timed_out']) {
                return 'ERROR_TIMEOUT';
            }
            $result = \substr($resp, \strpos($resp, "\r\n\r\n") + 4);
            if ($is_verbose) {
                echo "OK\n";
            }
        } else {
            if ($is_verbose) {
                echo "could not connect to host\n";
            }

            return 'ERROR_CONNECTING_'.$errnum;
        }

        $ex = \json_decode($result, true);
        if (!isset($ex['errorId'])) {
            if ($is_verbose) {
                echo "server returned error: {$ex['errorCode']} {$ex['errorDescription']} \n";
            }

            return $ex['errorCode'];
        }
        if (!isset($ex['taskId'])) {
            if ($is_verbose) {
                echo "server returned answer without id: $result\n";
            }

            return 'ERROR_TASK';
        }
        $captcha_id = $ex['taskId'];
        if ($is_verbose) {
            echo "captcha sent, got captcha ID $captcha_id\n";
        }

        return $captcha_id;
    }

    public function captcha_result(
        $captcha_id,
        $apikey,
        $is_verbose = false,
        $sendhost = 'api.anti-captcha.com')
    {
        $postdata = [
            'clientKey' => $apikey,
            'taskId' => $captcha_id,
        ];

        $poststr = \json_encode($postdata);

        if ($is_verbose) {
            echo 'connecting to host...';
        }
        $fp = \fsockopen($sendhost, 80, $errnum, $errmsg, 2);
        if (false != $fp) {
            if ($is_verbose) {
                echo "OK\n";
            }
            if ($is_verbose) {
                echo 'sending request...';
            }
            $header = "POST /getTaskResult HTTP/1.0\r\n";
            $header .= "Host: $sendhost\r\n";
            $header .= "Accept: application/json\r\n";
            $header .= "Content-Type: application/json\r\n";
            $header .= 'Content-Length: '.\strlen($poststr)."\r\n";
            $header .= "\r\n$poststr\r\n";
            // if ($is_verbose) echo $header;
            // exit;
            \fwrite($fp, $header);
            if ($is_verbose) {
                echo "OK\n";
            }
            if ($is_verbose) {
                echo 'getting response...';
            }
            $resp = '';
            \stream_set_timeout($fp, 5);
            do {
                $resp .= \fgets($fp, 4096);
                $info = \stream_get_meta_data($fp);
            } while (!\feof($fp) && !$info['timed_out']);
            \fclose($fp);
            if ($info['timed_out']) {
                return false; // "ERROR_TIMEOUT";
            }
            $result = \substr($resp, \strpos($resp, "\r\n\r\n") + 4);
            if ($is_verbose) {
                echo "OK\n";
            }
        } else {
            if ($is_verbose) {
                echo "could not connect to host\n";
            }

            return 'ERROR_CONNECTING_'.$errnum;
        }

        $ex = \json_decode($result, true);
        if ($ex['errorId']) {
            if ($is_verbose) {
                echo "server returned error: {$ex['errorCode']} {$ex['errorDescription']} \n";
            }

            return $ex['errorCode'];
        }
        if (!isset($ex['status']) || !isset($ex['errorId'])) {
            if ($is_verbose) {
                echo "server returned answer without status: $result\n";
            }

            return 'ERROR_STATUS';
        }

        if ('ready' !== $ex['status']) {
            if ($is_verbose) {
                echo "captcha is not ready yet\n";
            }

            return false;
        }
        if (!isset($ex['solution'])) {
            if ($is_verbose) {
                echo "server returned answer without solution: $result\n";
            }

            return 'ERROR_SOLUTION';
        }
        if (isset($ex['solution']['text'])) {
            $value = \trim($ex['solution']['text']);
            if ($is_verbose) {
                echo "captcha recognized as $value\n";
            }

            return $value;
        } elseif (isset($ex['solution']['gRecaptchaResponse'])) {
            $value = \trim($ex['solution']['gRecaptchaResponse']);
            if ($is_verbose) {
                echo "recaptcha response is $value\n";
            }

            return $value;
        } else {
            if ($is_verbose) {
                echo "server returned answer with unknown solution: $result\n";
            }

            return 'UNKNOWN_ERROR';
        }
    }

    public function captcha_bad(
        $captcha_id,
        $apikey,
        $is_verbose = false,
        $sendhost = 'api.anti-captcha.com')
    {
        $postdata = [
            'clientKey' => $apikey,
            'taskId' => $captcha_id,
        ];

        $poststr = \json_encode($postdata);

        if ($is_verbose) {
            echo 'connecting to host...';
        }
        $fp = \fsockopen($sendhost, 80, $errnum, $errmsg, 2);
        if (false != $fp) {
            if ($is_verbose) {
                echo "OK\n";
            }
            if ($is_verbose) {
                echo 'sending request...';
            }
            $header = "POST /reportIncorrectImageCaptcha HTTP/1.0\r\n";
            $header .= "Host: $sendhost\r\n";
            $header .= "Accept: application/json\r\n";
            $header .= "Content-Type: application/json\r\n";
            $header .= 'Content-Length: '.\strlen($poststr)."\r\n";
            $header .= "\r\n$poststr\r\n";
            // if ($is_verbose) echo $header;
            // exit;
            \fwrite($fp, $header);
            if ($is_verbose) {
                echo "OK\n";
            }
            if ($is_verbose) {
                echo 'getting response...';
            }
            $resp = '';
            \stream_set_timeout($fp, 5);
            do {
                $resp .= \fgets($fp, 4096);
                $info = \stream_get_meta_data($fp);
            } while (!\feof($fp) && !$info['timed_out']);
            \fclose($fp);
            if ($info['timed_out']) {
                return 'ERROR_TIMEOUT';
            }
            $result = \substr($resp, \strpos($resp, "\r\n\r\n") + 4);
            if ($is_verbose) {
                echo "OK\n";
            }
        } else {
            if ($is_verbose) {
                echo "could not connect to host\n";
            }

            return 'ERROR_CONNECTING_'.$errnum;
        }

        $ex = \json_decode($result, true);
        if ($ex['errorId']) {
            if ($is_verbose) {
                echo "server returned error: {$ex['errorCode']}\n";
            }

            return 'ERROR_INVALIDTASK';
        }
        if (!isset($ex['status']) || !isset($ex['errorId'])) {
            if ($is_verbose) {
                echo "server returned answer without status: $result\n";
            }

            return 'ERROR_STATUS';
        }

        if ($is_verbose) {
            echo "Captcha report accepted\n";
        }

        return 'OK_REPORT_RECORDED';
    }

    public function recaptcha_good(
        $captcha_id,
        $apikey,
        $is_verbose = false,
        $sendhost = 'api.anti-captcha.com')
    {
        $postdata = [
            'clientKey' => $apikey,
            'taskId' => $captcha_id,
        ];

        $poststr = \json_encode($postdata);

        if ($is_verbose) {
            echo 'connecting to host...';
        }
        $fp = \fsockopen($sendhost, 80, $errnum, $errmsg, 2);
        if (false != $fp) {
            if ($is_verbose) {
                echo "OK\n";
            }
            if ($is_verbose) {
                echo 'sending request...';
            }
            $header = "POST /reportCorrectRecaptcha HTTP/1.0\r\n";
            $header .= "Host: $sendhost\r\n";
            $header .= "Accept: application/json\r\n";
            $header .= "Content-Type: application/json\r\n";
            $header .= 'Content-Length: '.\strlen($poststr)."\r\n";
            $header .= "\r\n$poststr\r\n";
            // if ($is_verbose) echo $header;
            // exit;
            \fwrite($fp, $header);
            if ($is_verbose) {
                echo "OK\n";
            }
            if ($is_verbose) {
                echo 'getting response...';
            }
            $resp = '';
            \stream_set_timeout($fp, 5);
            do {
                $resp .= \fgets($fp, 4096);
                $info = \stream_get_meta_data($fp);
            } while (!\feof($fp) && !$info['timed_out']);
            \fclose($fp);
            if ($info['timed_out']) {
                return 'ERROR_TIMEOUT';
            }
            $result = \substr($resp, \strpos($resp, "\r\n\r\n") + 4);
            if ($is_verbose) {
                echo "OK\n";
            }
        } else {
            if ($is_verbose) {
                echo "could not connect to host\n";
            }

            return 'ERROR_CONNECTING_'.$errnum;
        }

        $ex = \json_decode($result, true);
        if ($ex['errorId']) {
            if ($is_verbose) {
                echo "server returned error: {$ex['errorCode']}\n";
            }

            return 'ERROR_INVALIDTASK';
        }
        if (!isset($ex['status']) || !isset($ex['errorId'])) {
            if ($is_verbose) {
                echo "server returned answer without status: $result\n";
            }

            return 'ERROR_STATUS';
        }

        if ($is_verbose) {
            echo "Captcha report accepted\n";
        }

        return 'OK_REPORT_RECORDED';
    }

    public function recaptcha_bad(
        $captcha_id,
        $apikey,
        $is_verbose = false,
        $sendhost = 'api.anti-captcha.com')
    {
        $postdata = [
            'clientKey' => $apikey,
            'taskId' => $captcha_id,
        ];

        $poststr = \json_encode($postdata);

        if ($is_verbose) {
            echo 'connecting to host...';
        }
        $fp = \fsockopen($sendhost, 80, $errnum, $errmsg, 2);
        if (false != $fp) {
            if ($is_verbose) {
                echo "OK\n";
            }
            if ($is_verbose) {
                echo 'sending request...';
            }
            $header = "POST /reportIncorrectRecaptcha HTTP/1.0\r\n";
            $header .= "Host: $sendhost\r\n";
            $header .= "Accept: application/json\r\n";
            $header .= "Content-Type: application/json\r\n";
            $header .= 'Content-Length: '.\strlen($poststr)."\r\n";
            $header .= "\r\n$poststr\r\n";
            // if ($is_verbose) echo $header;
            // exit;
            \fwrite($fp, $header);
            if ($is_verbose) {
                echo "OK\n";
            }
            if ($is_verbose) {
                echo 'getting response...';
            }
            $resp = '';
            \stream_set_timeout($fp, 5);
            do {
                $resp .= \fgets($fp, 4096);
                $info = \stream_get_meta_data($fp);
            } while (!\feof($fp) && !$info['timed_out']);
            \fclose($fp);
            if ($info['timed_out']) {
                return 'ERROR_TIMEOUT';
            }
            $result = \substr($resp, \strpos($resp, "\r\n\r\n") + 4);
            if ($is_verbose) {
                echo "OK\n";
            }
        } else {
            if ($is_verbose) {
                echo "could not connect to host\n";
            }

            return 'ERROR_CONNECTING_'.$errnum;
        }

        $ex = \json_decode($result, true);
        if ($ex['errorId']) {
            if ($is_verbose) {
                echo "server returned error: {$ex['errorCode']}\n";
            }

            return 'ERROR_INVALIDTASK';
        }
        if (!isset($ex['status']) || !isset($ex['errorId'])) {
            if ($is_verbose) {
                echo "server returned answer without status: $result\n";
            }

            return 'ERROR_STATUS';
        }

        if ($is_verbose) {
            echo "Captcha report accepted\n";
        }

        return 'OK_REPORT_RECORDED';
    }
}
