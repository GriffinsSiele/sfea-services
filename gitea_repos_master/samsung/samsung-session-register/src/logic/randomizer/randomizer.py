import random
import string
from typing import Callable

random.seed()

ascii_and_digits = string.ascii_letters + string.digits
months = [
    "Январь",
    "Февраль",
    "Март",
    "Апрель",
    "Май",
    "Июнь",
    "Июль",
    "Август",
    "Сентябрь",
    "Октябрь",
    "Ноябрь",
    "Декабрь",
]

# опечатки "ry" и "con" сделаны умышленно
email_providers = ("mail.ry", "yandex.ry", "google.con")


def random_string(length: int) -> str:
    """Возвращает рандомнцю строку длинной length

    :param length: Длинна строки
    :return: Рандомная строка
    """
    return "".join(random.choices(ascii_and_digits, k=length))


def random_fake_email(*args) -> str:
    """Возвращает не существующую почту, которая содержит ошибки в написании
    и с большой вероятностью никому не принадлежит

    :return: Не существующая почта
    """
    return random_string(12) + "@" + random.choice(email_providers)


def get_random_string_month(*args) -> str:
    """Возвращает случайный месяц в формате строки: "Январь" - "Декабрь"

    return: Месяц в формате строки
    """
    return random.choice(months)


def get_random_int_month(*args) -> int:
    """Возвращает случайный месяц в формате числа: 1 - 12

    return: Месяц в формате числа
    """
    return random.randint(1, 12)


def random_birthdate(month_strategy: Callable = get_random_string_month) -> dict:
    """Возвращает случайную дату рождения в диапазоне 1970-2004 гг.

    :return: Дата рождения в формате {"day": 1-28, "month": Январь-Декабрь, "year": 1970-2004}.
    """
    return {
        "day": random.randint(1, 28),
        "month": month_strategy(months),
        "year": random.randint(1970, 2004),
    }


def random_fake_person(*args) -> dict:
    """Возвращает не существующего человека для получения сессии.

    :return: Словарь {"first_name": <случайная строка длинной 10 символов>,
        "last_name": <случайная строка длинной 12 символов>, "birthdate": <дата рождения>}
    """
    return {
        "first_name": random_string(10),
        "last_name": random_string(12),
        "birthdate": random_birthdate(),
    }


def random_fake_person_to_prolong(*args) -> dict:
    """Возвращает не существующего человека для пролонгации сессии person.

    :return: Словарь {"first_name": <случайная строка длинной 10 символов>,
        "last_name": <случайная строка длинной 12 символов>, "birthdate": <дата рождения>}
    """
    birthdate = random_birthdate(month_strategy=get_random_int_month)
    birthdate_str = "{:4d}{:02d}{:02d}".format(
        birthdate.get("year", 0), birthdate.get("month", 0), birthdate.get("day", 0)
    )
    return {
        "first_name": random_string(10),
        "last_name": random_string(12),
        "birthdate": birthdate_str,
    }


def random_fake_person_with_mail(*args) -> dict:
    """Возвращает не существующего человека получения сессии в обработчике name.

    :return: Словарь {"account_login": <не существующая почта>, "first_name": <случайная строка длинной 10 символов>,
        "last_name": <случайная строка длинной 12 символов>, "birthdate": <дата рождения>}
    """
    person = random_fake_person()
    person["account_login"] = random_fake_email()
    return person


def random_fake_person_to_prolong_with_email(*args) -> dict:
    """Возвращает не существующего человека для пролонгации сессии name.

    :return: Словарь {"account_login": <не существующая почта>, "first_name": <случайная строка длинной 10 символов>,
        "last_name": <случайная строка длинной 12 символов>, "birthdate": <дата рождения>}
    """
    person = random_fake_person_to_prolong()
    person["account_login"] = random_fake_email()
    return person
