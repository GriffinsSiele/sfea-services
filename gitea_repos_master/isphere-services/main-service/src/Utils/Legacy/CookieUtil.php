<?php

declare(strict_types=1);

namespace App\Utils\Legacy;

class CookieUtil
{
    public function parse_cookies($header)
    {
        return CookieUtilStatic::parse_cookies($header);
    }

    public function cookies_header($cookies)
    {
        return CookieUtilStatic::cookies_header($cookies);
    }

    public function cookies_str($cookies)
    {
        return CookieUtilStatic::cookies_str($cookies);
    }

    public function str_cookies($str)
    {
        return CookieUtilStatic::str_cookies($str);
    }
}
