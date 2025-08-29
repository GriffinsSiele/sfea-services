<?php

declare(strict_types=1);

namespace App\Utils\Legacy;

class StrUtilStatic
{
    public static function str_between($str, $start_str, $finish_str)
    {
        $res = '';
        $start = \strpos($str, $start_str);
        if (false !== $start) {
            $start += \strlen($start_str);
            $finish = \strpos($str, $finish_str, $start);
            $res = \substr($str, $start, $finish - $start);
        }

        return $res;
    }

    public static function str_with($str, $start_str, $finish_str)
    {
        $res = '';
        $start = \strpos($str, $start_str);
        if (false !== $start) {
            $finish = \strpos($str, $finish_str, $start);
            $finish += \strlen($finish_str);
            $res = \substr($str, $start, $finish - $start);
        }

        return $res;
    }

    public static function str_uprus($text)
    {
        $up = [
            'а' => 'А',
            'б' => 'Б',
            'в' => 'В',
            'г' => 'Г',
            'д' => 'Д',
            'е' => 'Е',
            'ё' => 'Ё',
            'ж' => 'Ж',
            'з' => 'З',
            'и' => 'И',
            'й' => 'Й',
            'к' => 'К',
            'л' => 'Л',
            'м' => 'М',
            'н' => 'Н',
            'о' => 'О',
            'п' => 'П',
            'р' => 'Р',
            'с' => 'С',
            'т' => 'Т',
            'у' => 'У',
            'ф' => 'Ф',
            'х' => 'Х',
            'ц' => 'Ц',
            'ч' => 'Ч',
            'ш' => 'Ш',
            'щ' => 'Щ',
            'ъ' => 'Ъ',
            'ы' => 'Ы',
            'ь' => 'Ь',
            'э' => 'Э',
            'ю' => 'Ю',
            'я' => 'Я',
        ];
        if (\preg_match('/[а-я]/', $text)) {
            $text = \strtr($text, $up);
        }

        return $text;
    }

    public static function str_translit($text)
    {
        $trans = [
            'КС' => 'X',
            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Ё' => 'E',
            'Ж' => 'ZH',
            'З' => 'Z',
            'И' => 'I',
            'Й' => 'Y',
            'К' => 'K',
            'Л' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ф' => 'F',
            'Х' => 'H',
            'Ц' => 'TS',
            'Ч' => 'CH',
            'Ш' => 'SH',
            'Щ' => 'SH',
            'ЬЕ' => 'YE',
            'ЬЁ' => 'YO',
            'Ь' => '',
            'Ы' => 'Y',
            'Ъ' => '',
            'Э' => 'E',
            'Ю' => 'YU',
            'Я' => 'YA',
        ];
        $text = self::str_uprus($text);
        if (\preg_match('/[А-Я]/', $text)) {
            $text = \strtr($text, $trans);
        }

        return $text;
    }
}
