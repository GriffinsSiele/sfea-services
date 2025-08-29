<?php

declare(strict_types=1);

namespace App\Utils\Legacy;

class NeuroUtil
{
    public const NEURO_SOURCES = [
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
    ];

    public function neuro_post($image, $method)
    {
        //    return 'ERROR_DISABLED';
        $boundary = '--------------------------'.\microtime(true);
        $post_data = '--'.$boundary."\r\n".
            "Content-Disposition: form-data; name=\"image\"; filename=\"captcha.jpg\"\r\n".
            "Content-Type: image/jpeg\r\n\r\n".
            $image."\r\n".
            '--'.$boundary."--\r\n";
        $context = \stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: multipart/form-data; boundary='.$boundary,
                'content' => $post_data,
                'timeout' => 5,
            ],
        ]);
        $tries = 0;
        do {
            $result = \file_get_contents('http://172.16.1.25'.(4 - $tries % 2).':8001/'.$method, false, $context);
        } while (!$result && ++$tries <= 3);
        $res = \json_decode($result, true);
        if (!$res) {
            return 'ERROR_BAD_ANSWER';
        }
        if (\str_contains($res['text'], '[UNK]') || \str_contains($res['text'], '-')) {
            //        return 'ERROR_CAPTCHA_UNSOLVABLE';
        }

        return \strtr($res['text'], [' ' => '', '-' => '9', '[UNK]' => '9']);
    }

    public function neuro_token($method, $sitekey, $action = '')
    {
        $context = \stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 2,
            ],
        ]);
        $tries = 0;
        do {
            $result = \file_get_contents(
                'http://172.16.1.25'.(4 - $tries % 2).":8003/$method?sitekey=$sitekey".($action ? "&action=$action" : ''),
                false,
                $context
            );
        } while (!$result && ++$tries <= 3);
        $res = \json_decode($result, true);
        if (!$res) {
            return 'ERROR_BAD_ANSWER';
        }

        return 200 == $res['code'] ? $res['token'] : 'ERROR_NO_TOKEN';
    }
}
