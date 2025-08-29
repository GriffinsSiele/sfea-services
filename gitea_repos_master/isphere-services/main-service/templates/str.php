<?php

use App\Utils\Legacy\StrUtilStatic;
use JetBrains\PhpStorm\Deprecated;

#[Deprecated]
function str_between($str, $start_str, $finish_str)
{
    return StrUtilStatic::str_between($str, $start_str, $finish_str);
}

#[Deprecated]
function str_with($str, $start_str, $finish_str)
{
    return StrUtilStatic::str_with($str, $start_str, $finish_str);
}

#[Deprecated]
function str_uprus($text)
{
    return StrUtilStatic::str_uprus($text);
}

#[Deprecated]
function str_translit($text)
{
    return StrUtilStatic::str_translit($text);
}
