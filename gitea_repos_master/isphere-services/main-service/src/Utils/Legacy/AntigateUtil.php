<?php

declare(strict_types=1);

namespace App\Utils\Legacy;

class AntigateUtil
{
    public function antigate_post(
        $image,
        $apikey,
        $is_verbose = false,
        $sendhost = 'rucaptcha.com',
        $is_phrase = 0,
        $is_regsense = 0,
        $is_numeric = 0,
        $min_len = 0,
        $max_len = 0,
        $is_russian = 0
    ) {
        if ($is_verbose) {
            echo "is_numeric: $is_numeric\n";
        }

        if ('GIF' == \substr($image, 0, 3)) {
            $ext = 'gif';
        } elseif ('PNG' == \substr($image, 1, 3)) {
            $ext = 'png';
        } elseif (255 == \ord(\substr($image, 0, 1))) {
            $ext = 'jpg';
        } else {
            $filename = $image;
            if (!\file_exists($filename)) {
                if ($is_verbose) {
                    echo "file $filename not found\n";
                }

                return false;
            }
            $fp = \fopen($filename, 'r');
            if (false != $fp) {
                $image = '';
                while (!\feof($fp)) {
                    $image .= \fgets($fp, 4096);
                }
                \fclose($fp);
                $ext = \substr($filename, \strpos($filename, '.') + 1);
            } else {
                if ($is_verbose) {
                    echo "could not read file $filename\n";
                }

                return false;
            }
        }
        $postdata = [
            'method' => 'base64',
            'key' => $apikey,
            'body' => \base64_encode($image),
            'ext' => $ext,
            'phrase' => $is_phrase,
            'regsense' => $is_regsense,
            'numeric' => $is_numeric,
            'min_len' => $min_len,
            'max_len' => $max_len,
            'is_russian' => $is_russian,
        ];
        if ($is_russian) {
            $postdata['language'] = 1;
            $postdata['lang'] = 'ru';
        }

        $poststr = '';
        foreach ($postdata as $name => $value) {
            if ('' !== $poststr) {
                $poststr .= '&';
            }
            $poststr .= $name.'='.\urlencode((string) $value);
        }

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
            $header = "POST /in.php HTTP/1.0\r\n";
            $header .= "Host: $sendhost\r\n";
            $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
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

        if (\str_contains($result, 'ERROR')) {
            if ($is_verbose) {
                echo "server returned error: $result\n";
            }

            return $result;
        }
        $ex = \explode('|', $result);
        if (!isset($ex[1])) {
            if ($is_verbose) {
                echo "server returned answer without id: $result\n";
            }

            return "UNKNOWN_ERROR ($result)";
        }
        $captcha_id = $ex[1];
        if ($is_verbose) {
            echo "captcha sent, got captcha ID $captcha_id\n";
        }

        return $captcha_id;
    }

    public function antigate_get(
        $captcha_id,
        $apikey,
        $is_verbose = false,
        $sendhost = 'rucaptcha.com'
    ) {
        $context = \stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 5,
            ],
        ]);
        $result = \file_get_contents("http://$sendhost/res.php?key=".$apikey.'&action=get&id='.$captcha_id, false, $context);
        if (\str_contains($result, 'ERROR')) {
            if ($is_verbose) {
                echo "server returned error: $result\n";
            }

            return $result;
        }
        if (\str_contains($result, 'NOT_READY')) {
            if ($is_verbose) {
                echo "captcha is not ready yet\n";
            }

            return false;
        }
        $ex = \explode('|', $result);
        $result = \trim($ex[0]);
        if ('OK' == $result) {
            $value = \trim($ex[1]);
            if ($is_verbose) {
                echo "captcha recognized as $value\n";
            }

            return $value;
        } else {
            if ($is_verbose) {
                echo "captcha not recognized with code $result\n";
            }

            return $result;
        }
    }

    public function antigate_reportgood(
        $captcha_id,
        $apikey,
        $is_verbose = false,
        $sendhost = 'rucaptcha.com'
    ) {
        //    return 'OK_REPORT_RECORDED';
        $context = \stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 1,
            ],
        ]);
        $result = \file_get_contents("http://$sendhost/res.php?key=".$apikey.'&action=reportgood&id='.$captcha_id, false, $context);
        if (\str_contains($result, 'ERROR')) {
            if ($is_verbose) {
                echo "server returned error: $result\n";
            }

            return $result;
        }
        $ex = \explode('|', $result);
        $result = \trim($ex[0]);
        if ('OK_REPORT_RECORDED' == $result) {
            if ($is_verbose) {
                echo "captcha reported as good\n";
            }

            return $result;
        } else {
            if ($is_verbose) {
                echo "captcha report error\n";
            }

            return $result;
        }
    }

    public function antigate_reportbad(
        $captcha_id,
        $apikey,
        $is_verbose = false,
        $sendhost = 'rucaptcha.com'
    ) {
        //    return 'OK_REPORT_RECORDED';
        $context = \stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 1,
            ],
        ]);
        $result = \file_get_contents("http://$sendhost/res.php?key=".$apikey.'&action=reportbad&id='.$captcha_id, false, $context);
        if (\str_contains($result, 'ERROR')) {
            if ($is_verbose) {
                echo "server returned error: $result\n";
            }

            return $result;
        }
        $ex = \explode('|', $result);
        $result = \trim($ex[0]);
        if ('OK_REPORT_RECORDED' == $result) {
            if ($is_verbose) {
                echo "captcha reported as bad\n";
            }

            return $result;
        } else {
            if ($is_verbose) {
                echo "captcha report error\n";
            }

            return $result;
        }
    }
}
