<?php

declare(strict_types=1);

namespace App\Contract;

enum ScalarType: string
{
    case BIRTHDAY = 'birthday';
    case INN = 'inn';
    case NAME = 'name';
    case NAME_PATRONYMIC_SURNAME = 'name_patronymic_surname';
    case PATRONYMIC = 'patronymic';
    case PHONE = 'phone';
    case RUSSIAN_PASSPORT = 'russian_passport';
    case RUSSIAN_PASSPORT_NUMBER = 'russian_passport_number';
    case RUSSIAN_PASSPORT_SERIES = 'russian_passport_series';
    case RUSSIAN_REGION = 'russian_region';
    case SURNAME = 'surname';
    case SURNAME_NAME_PATRONYMIC = 'surname_name_patronymic';
    case EMAIL = 'email';

    case UNKNOWN = 'unknown';
}
