<?php

use App\Utils\Legacy\CookieUtilStatic;
use JetBrains\PhpStorm\Deprecated;

#[Deprecated]
function parse_cookies($header)
{
    return CookieUtilStatic::parse_cookies($header);
}

#[Deprecated]
function cookies_header($cookies)
{
    return CookieUtilStatic::cookies_header($cookies);
}

#[Deprecated]
function cookies_str($cookies)
{
    return CookieUtilStatic::cookies_str($cookies);
}

#[Deprecated]
function str_cookies($str)
{
    return CookieUtilStatic::str_cookies($str);
}
