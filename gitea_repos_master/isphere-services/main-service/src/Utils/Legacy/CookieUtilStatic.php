<?php

declare(strict_types=1);

namespace App\Utils\Legacy;

class CookieUtilStatic
{
    public static function parse_cookies($header)
    {
        $cookies = [];
        foreach ($header as $line) {
            $cookie_str = 'Set-Cookie:';
            $pos = \strpos($line, $cookie_str);
            if (false === $pos) {
                $cookie_str = 'set-cookie:';
                $pos = \strpos($line, $cookie_str);
            }
            if (false !== $pos) {
                $line = \substr($line, $pos + \strlen($cookie_str));
                $pos = \strpos($line, ';');
                if ($pos) {
                    $line = \trim(\substr($line, 0, $pos));
                }
                $pos = \strpos($line, '=');
                if ($pos) {
                    $cookies[\substr($line, 0, $pos)] = \substr($line, $pos + 1);
                }
            }
        }

        return $cookies;
    }

    public static function cookies_header($cookies)
    {
        if (\count($cookies)) {
            $names = \array_keys($cookies);
            $cookie = [];
            foreach ($cookies as $key => $val) {
                if ('DELETED' !== $val) {
                    $cookie[] = $key.'='.$val;
                }
            }
            if (\count($cookie)) {
                return 'Cookie: '.\trim(\implode('; ', $cookie)).";\r\n";
            }
        }

        return '';
    }

    public static function cookies_str($cookies)
    {
        $cookie = [];
        foreach ($cookies as $key => $val) {
            if ('DELETED' !== $val) {
                $cookie[] = $key.'='.$val;
            }
        }

        return \trim(\implode('; ', $cookie));
    }

    public static function str_cookies($str)
    {
        $cookies = [];
        $cookie = \explode('; ', $str);
        foreach ($cookie as $line) {
            $pos = \strpos($line, '=');
            if ($pos) {
                $cookies[\substr($line, 0, $pos)] = \substr($line, $pos + 1);
            }
        }

        return $cookies;
    }
}
